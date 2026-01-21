@extends('backend.layouts.app')

@section('title', 'Edit Engagement')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Engagement</h1>
                    <p class="mt-1 text-sm text-gray-600">Update engagement details</p>
                </div>
                <a href="{{ route('admin.engagements.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Back to Engagements
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('admin.engagements.update', $engagement->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('backend.engagements._form')
                
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('admin.engagements.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                        Update Engagement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection