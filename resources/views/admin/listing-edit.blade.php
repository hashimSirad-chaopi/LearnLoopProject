@extends('layouts.admin')

@section('title', 'Edit Listing')
@section('page-title', 'Edit Listing')
@section('page-subtitle', 'Update the title, linked skill, and description.')

@section('content')

<section class="admin-page">

    <a href="{{ route('admin.listings') }}" class="back-link">&larr; Back to listings</a>

    <div class="detail-section">

        <form method="POST" action="{{ route('admin.listings.update', $listing) }}" class="action-form" style="max-width: 560px;">
            @csrf @method('PATCH')

            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="{{ old('title', $listing->title) }}" required>

            <label for="skill_id">Skill / Category</label>
            <select name="skill_id" id="skill_id" required>
                <option value="" disabled {{ old('skill_id', $listing->skill_id) ? '' : 'selected' }}>
                    Select a skill...
                </option>

                @forelse ($categories as $category)
                    <optgroup label="{{ $category->name }}">
                        @forelse ($category->skills as $skill)
                            <option value="{{ $skill->id }}"
                                {{ (int) old('skill_id', $listing->skill_id) === $skill->id ? 'selected' : '' }}>
                                {{ $skill->name }}
                            </option>
                        @empty
                            <option value="" disabled>No skills yet in this category</option>
                        @endforelse
                    </optgroup>
                @empty
                    <option value="" disabled>No categories set up yet</option>
                @endforelse
            </select>

            @if ($categories->isEmpty() || $categories->every(fn ($c) => $c->skills->isEmpty()))
                <p class="cell-muted">
                    No skills exist yet. <a href="{{ route('admin.categories.index') }}" class="cell-link">Add some categories and skills</a> first.
                </p>
            @endif

            @if (! $listing->skill_id && $listing->category)
                <p class="cell-muted">
                    This listing still uses the old free-text category "<strong>{{ $listing->category }}</strong>".
                    Pick a matching skill above to link it properly.
                </p>
            @endif

            <label for="description">Description</label>
            <textarea name="description" id="description" rows="5" required>{{ old('description', $listing->description) }}</textarea>

            <button type="submit" class="btn-primary">Save changes</button>
        </form>

    </div>

</section>

@endsection
