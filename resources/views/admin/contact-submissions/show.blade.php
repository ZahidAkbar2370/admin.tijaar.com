@extends('admin.layouts.app')

@section('title', 'Contact Submission')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.contact-submissions.index') }}" class="text-primary text-sm hover:underline">← Back</a>
</div>
<div class="w-full min-w-0 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <p class="text-sm text-gray-500">{{ $contactSubmission->created_at->copy()->setTimezone('Asia/Karachi')->format('M d, Y g:i A') }} (Pakistan Time)</p>
    <h2 class="text-xl font-bold text-gray-900 mt-2">{{ $contactSubmission->name }}</h2>
    <p class="text-gray-600">{{ $contactSubmission->email }}</p>
    @if ($contactSubmission->subject)
    <p class="font-medium text-gray-900 mt-4">Subject: {{ $contactSubmission->subject }}</p>
    @endif
    <div class="mt-6 p-4 bg-gray-50 rounded-xl">
        <p class="text-gray-700 whitespace-pre-wrap">{{ $contactSubmission->message }}</p>
    </div>
</div>
@endsection
