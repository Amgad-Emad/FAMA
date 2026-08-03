<?php

namespace App\Data\Feed;

use App\Data\BaseData;
use Illuminate\Http\UploadedFile;

/**
 * The typed input for CreatePost (feed-spec §4): a note or media post's body,
 * skill tags, and uploaded media set. Project shares travel through
 * ShareProjectAsPost instead — they carry a Project, not uploads.
 */
class CreatePostData extends BaseData
{
    /**
     * @param  array<int, int>  $skillIds
     * @param  array<int, UploadedFile>  $media
     */
    public function __construct(
        public string $postType,
        public ?string $body = null,
        public array $skillIds = [],
        public array $media = [],
        public array $mediaAlt = [],
    ) {
    }
}
