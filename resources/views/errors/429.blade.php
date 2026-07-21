@extends('errors.layout')

@section('code', '429')
@section('title', __("Too many requests"))
@section('message', __("You're moving a little fast. Give it a moment and try again."))
