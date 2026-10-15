@extends('errors.layout')

@section('code', '429')
@section('title', __("Too many requests"))
@section('heading', __("Slow down a little"))
@section('message', __("That was a lot of requests in a short time. Wait a moment, then try again."))
