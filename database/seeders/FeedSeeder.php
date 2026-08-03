<?php

namespace Database\Seeders;

use App\Actions\Feed\SyncLinks;
use App\Actions\Feed\SyncMentions;
use App\Models\Brand;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\RichTextParser;
use App\States\Post\Published;
use Database\Seeders\Concerns\GeneratesCoverImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * The feed demo dataset (Prompt 5F) — the UI prompts are evaluated against
 * this, so realism matters: ~40 published talents across all six Skills,
 * ~15 brands, ~300 posts (notes / media / Project shares) with plausible
 * Arabic and English bodies, @mentions, Instagram/Behance/TikTok links,
 * skill tags, a follow graph, likes and threaded comments. Sizes come from
 * config('feed.seeder') so tests can shrink them.
 *
 * Bodies run through the real RichTextParser + Sync actions. Counters are
 * RECONCILED at the end (CounterService::reconcile) rather than trusted to
 * observers — DatabaseSeeder runs WithoutModelEvents, which mutes them.
 */
class FeedSeeder extends Seeder
{
    use GeneratesCoverImages;

    public function run(): void
    {
        mt_srand(20260722); // deterministic dataset

        $talents = $this->ensureTalents((int) config('feed.seeder.talents'));
        $brands = $this->ensureBrands((int) config('feed.seeder.brands'));

        $posts = $this->createPosts($talents, (int) config('feed.seeder.posts'));

        $this->weaveFollows($talents, $brands);
        $this->scatterLikes($posts, $talents, $brands);
        $this->threadComments($posts, $talents, $brands);

        // DatabaseSeeder mutes model events (WithoutModelEvents), so the
        // observer-driven counters never fired — recompute them from source.
        app(\App\Services\CounterService::class)->reconcile();
    }

    /**
     * Top the published-talent pool up to $count, each with 1–2 Skills.
     *
     * @return Collection<int, Talent>
     */
    private function ensureTalents(int $count): Collection
    {
        $types = TalentType::all();
        $existing = Talent::query()->where('is_published', true)->with('talentTypes')->get();

        $needed = max(0, $count - $existing->count());

        $created = Talent::factory()->count($needed)->create()->each(function (Talent $talent) use ($types): void {
            $picks = $types->random(mt_rand(1, 2));

            foreach ($picks->values() as $index => $type) {
                $talent->talentTypes()->attach($type->id, ['is_primary' => $index === 0, 'position' => $index]);
            }

            // A portrait avatar so the feed can be judged honestly (round 3).
            $talent->addMedia($this->cover('avatar-'.$talent->slug, 640, 800))->toMediaCollection('avatar');
        });

        return $existing->concat($created)->values();
    }

    /**
     * Top the published-brand pool up to $count.
     *
     * @return Collection<int, Brand>
     */
    private function ensureBrands(int $count): Collection
    {
        $existing = Brand::query()->where('is_published', true)->get();
        $needed = max(0, $count - $existing->count());

        $created = Brand::factory()->count($needed)->create(['is_published' => true])->each(
            fn (Brand $brand) => $brand->addMedia($this->cover('logo-'.$brand->slug, 640, 640))->toMediaCollection('logo')
        );

        return $existing->concat($created)->values();
    }

    /**
     * Create the post corpus: ~40% notes, ~45% media, ~15% Project shares.
     *
     * @param  Collection<int, Talent>  $talents
     * @return Collection<int, Post>
     */
    private function createPosts(Collection $talents, int $count): Collection
    {
        $parser = app(RichTextParser::class);
        $syncMentions = app(SyncMentions::class);
        $syncLinks = app(SyncLinks::class);

        $posts = collect();

        for ($i = 0; $i < $count; $i++) {
            /** @var Talent $author */
            $author = $talents->random();
            $roll = mt_rand(1, 100);

            $post = match (true) {
                $roll <= 40 => $this->notePost($author, $talents, $i),
                $roll <= 85 => $this->mediaPost($author, $talents, $i),
                default => $this->sharePost($author, $talents, $i),
            };

            if ($post === null) {
                continue;
            }

            $this->tagSkills($post, $author);

            if (filled($post->body)) {
                $parsed = $parser->parse($post->body);
                $syncMentions($post, $parsed);
                $syncLinks($post, $parsed);
            }

            $posts->push($post);
        }

        return $posts;
    }

    /**
     * A text-only note with a plausible EN/AR body.
     */
    private function notePost(Talent $author, Collection $talents, int $i): Post
    {
        return Post::factory()->byAuthor($author)->create([
            'post_type' => 'note',
            'body' => $this->body($talents, $i),
            'published_at' => now()->subDays(mt_rand(0, 45))->subMinutes(mt_rand(0, 1400)),
            'state' => Published::$name,
        ]);
    }

    /**
     * A media post with 1–4 generated editorial images.
     */
    private function mediaPost(Talent $author, Collection $talents, int $i): Post
    {
        $post = Post::factory()->byAuthor($author)->create([
            'post_type' => 'media',
            'body' => mt_rand(1, 100) <= 70 ? $this->body($talents, $i) : null,
            'published_at' => now()->subDays(mt_rand(0, 45))->subMinutes(mt_rand(0, 1400)),
            'state' => Published::$name,
        ]);

        $shots = mt_rand(1, 4);

        for ($s = 0; $s < $shots; $s++) {
            $post->addMedia($this->cover("feed-{$i}-{$s}", 1080, mt_rand(1, 2) === 1 ? 1350 : 810))
                ->toMediaCollection('post_media');
        }

        return $post;
    }

    /**
     * A Project share (ADR-F2) — skipped when the author has no Projects.
     */
    private function sharePost(Talent $author, Collection $talents, int $i): ?Post
    {
        $project = Project::query()->where('talent_id', $author->id)->inRandomOrder()->first()
            ?? Project::factory()->create([
                'talent_id' => $author->id,
                'talent_type_id' => $author->talentTypes()->first()?->id,
            ]);

        return Post::factory()->byAuthor($author)->create([
            'post_type' => 'project_share',
            'body' => mt_rand(1, 100) <= 60 ? $this->body($talents, $i) : null,
            'project_id' => $project->id,
            'shared_snapshot' => [
                'title' => $project->getTranslations('title'),
                'cover_media_path' => $project->getFirstMediaUrl('cover') ?: null,
                'client_name' => $project->client_name,
                'year' => $project->year,
                'media_count' => $project->loadMissing('media')->media->count(),
                'skill_ids' => [],
                'shared_at' => now()->toIso8601String(),
            ],
            'published_at' => now()->subDays(mt_rand(0, 45)),
            'state' => Published::$name,
        ]);
    }

    /**
     * Tag the post with 1–2 of the author's own Skills.
     */
    private function tagSkills(Post $post, Talent $author): void
    {
        $own = $author->talentTypes()->pluck('talent_types.id');

        if ($own->isEmpty()) {
            return;
        }

        $post->skills()->attach($own->random(min(mt_rand(1, 2), $own->count())));
    }

    /**
     * A plausible MENA feed body — EN/AR mix, occasional @mention and link.
     */
    private function body(Collection $talents, int $i): string
    {
        $en = [
            'Golden hour on the Corniche never disappoints. Full set soon.',
            'Wrapped a two-day lookbook shoot in Zamalek — the light was pure honey.',
            'Casting was intense, the energy on set even better. Alhamdulillah.',
            'New editorial live. Shot on location in Fayoum with an incredible crew.',
            'Testing new looks for SS26. The tailoring on this collection is unreal.',
            'BTS from the night shoot in Downtown Cairo. Neon everywhere.',
            'Gouna diaries: salt air, linen, and a camera that never sleeps.',
            'Moodboard to final frame in 48 hours. Proud of this one.',
        ];

        $ar = [
            'كواليس جلسة تصوير النهاردة في وسط البلد — الإضاءة كانت خيالية.',
            'الحمد لله، خلصنا حملة كاملة في يومين. فريق أكتر من رائع.',
            'جلسة تصوير جديدة على البحر في الجونة. الألوان هتبهركم.',
            'أول نظرة من مجموعة ربيع ٢٠٢٦ — استنوا الصور كاملة قريب.',
            'التفاصيل هي كل حاجة. قصّة القماش دي أخدت مننا أسبوع كامل.',
            'شكراً لكل الفريق على المجهود في شوت النهاردة. صور جاية في السكة.',
        ];

        $body = mt_rand(1, 100) <= 35 ? $ar[$i % count($ar)] : $en[$i % count($en)];

        if (mt_rand(1, 100) <= 30) {
            $body .= ' مع @'.$talents->random()->slug;
        } elseif (mt_rand(1, 100) <= 30) {
            $body .= ' with @'.$talents->random()->slug;
        }

        if (mt_rand(1, 100) <= 25) {
            $handle = $talents->random()->slug;
            $links = [
                "https://instagram.com/{$handle}",
                "https://www.behance.net/{$handle}",
                "https://www.tiktok.com/@{$handle}",
            ];
            $body .= ' '.$links[$i % 3];
        }

        return $body;
    }

    /**
     * Weave the follow graph: brands scout talents, talents follow peers.
     *
     * @param  Collection<int, Talent>  $talents
     * @param  Collection<int, Brand>  $brands
     */
    private function weaveFollows(Collection $talents, Collection $brands): void
    {
        foreach ($brands as $brand) {
            foreach ($talents->random(min(mt_rand(3, 8), $talents->count()))->values() as $talent) {
                Follow::query()->firstOrCreate([
                    'follower_type' => 'brand', 'follower_id' => $brand->id,
                    'followable_type' => 'talent', 'followable_id' => $talent->id,
                ], ['created_at' => now()->subDays(mt_rand(0, 60))]);
            }
        }

        foreach ($talents as $talent) {
            foreach ($talents->except([$talent->id])->random(min(mt_rand(2, 6), $talents->count() - 1))->values() as $peer) {
                Follow::query()->firstOrCreate([
                    'follower_type' => 'talent', 'follower_id' => $talent->id,
                    'followable_type' => 'talent', 'followable_id' => $peer->id,
                ], ['created_at' => now()->subDays(mt_rand(0, 60))]);
            }
        }
    }

    /**
     * Scatter likes across the corpus (observer-counted).
     *
     * @param  Collection<int, Post>  $posts
     * @param  Collection<int, Talent>  $talents
     * @param  Collection<int, Brand>  $brands
     */
    private function scatterLikes(Collection $posts, Collection $talents, Collection $brands): void
    {
        $actors = $talents->map(fn ($t) => ['talent', $t->id])
            ->concat($brands->map(fn ($b) => ['brand', $b->id]))
            ->values();

        foreach ($posts as $post) {
            foreach ($actors->random(min(mt_rand(0, 12), $actors->count()))->values() as [$type, $id]) {
                Like::query()->firstOrCreate([
                    'likeable_type' => 'post', 'likeable_id' => $post->id,
                    'liker_type' => $type, 'liker_id' => $id,
                ]);
            }
        }
    }

    /**
     * Thread comments + replies on roughly half the corpus.
     *
     * @param  Collection<int, Post>  $posts
     * @param  Collection<int, Talent>  $talents
     * @param  Collection<int, Brand>  $brands
     */
    private function threadComments(Collection $posts, Collection $talents, Collection $brands): void
    {
        $en = ['Stunning frame.', 'The light here is unreal.', 'We need this energy on our next campaign.', 'Iconic.', 'Booking you for our next shoot, inshallah.'];
        $ar = ['شغل عالي جداً 🔥', 'الإضاءة دي تحفة.', 'عايزين نشتغل مع بعض قريب!', 'جمال يا هندسة.', 'التفاصيل مذهلة بجد.'];

        foreach ($posts as $post) {
            if (mt_rand(1, 100) > 55) {
                continue;
            }

            $count = mt_rand(1, 4);

            for ($c = 0; $c < $count; $c++) {
                $isBrand = mt_rand(1, 100) <= 35 && $brands->isNotEmpty();
                $author = $isBrand ? $brands->random() : $talents->random();

                $comment = Comment::factory()->byAuthor($author)->create([
                    'post_id' => $post->id,
                    'body' => mt_rand(1, 2) === 1 ? $en[array_rand($en)] : $ar[array_rand($ar)],
                    'created_at' => $post->published_at?->addMinutes(mt_rand(10, 4000)) ?? now(),
                ]);

                if (mt_rand(1, 100) <= 40) {
                    $replier = $talents->random();
                    Comment::factory()->byAuthor($replier)->replyTo($comment)->create([
                        'body' => mt_rand(1, 2) === 1 ? 'Thank you! 🙏' : 'تسلم يا فنان ❤️',
                        'created_at' => $comment->created_at->addMinutes(mt_rand(5, 500)),
                    ]);
                }
            }
        }
    }
}
