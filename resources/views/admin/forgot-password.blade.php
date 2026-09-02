@extends('admin.layout')

@section('title', 'Forgot Password')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name') }}</h1>
                <p class="text-gray-500 mt-1">Admin – Reset Password</p>
            </div>

            @if (session('status'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                    <p class="text-green-700 text-sm">{{ session('status') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-600 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit"
                    class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition">
                    Send Reset Link
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500">
                <a href="{{ route('admin.login') }}" class="text-primary hover:underline">Back to Login</a>
            </p>
        </div>
    </div>
</div>
@endsection
