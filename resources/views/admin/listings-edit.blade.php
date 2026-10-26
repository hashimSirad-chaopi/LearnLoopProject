<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Edit Listing</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

       @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Edit Listing</h1>
                <p>Update this listing's details.</p>
            </div>
        </header>

        <section class="listing-page">
            @include('partials.flash')
            <form class="settings-form" method="POST" action="{{ route('admin.listings.update', $listing->id) }}">
                @csrf
                @method('PUT')

                <div class="settings-field">
                    <label for="user_id">User</label>
                    <select id="user_id" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $listing->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $listing->title) }}">
                    @error('title') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="wants_skill_id">Wants to learn</label>
                    <select id="wants_skill_id" name="wants_skill_id" required>
                        <option value="">Choose a skill</option>
                        @foreach($skills as $skill)
                            <option value="{{ $skill->id }}" {{ (string) old('wants_skill_id', $listing->wants_skill_id) === (string) $skill->id ? 'selected' : '' }}>{{ $skill->title }}</option>
                        @endforeach
                    </select>
                    @error('wants_skill_id') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="offers_skill_id">Can teach</label>
                    <select id="offers_skill_id" name="offers_skill_id">
                        <option value="">Nothing yet (learner-only listing)</option>
                        @foreach($skills as $skill)
                            <option value="{{ $skill->id }}" {{ (string) old('offers_skill_id', $listing->offers_skill_id) === (string) $skill->id ? 'selected' : '' }}>{{ $skill->title }}</option>
                        @endforeach
                    </select>
                    <p class="ll-muted">Leave empty when the member has no skill to teach yet. They can still post what they want to learn, and a member who has passed every lesson of that skill can offer to teach it. The category follows the skill.</p>
                    @error('offers_skill_id') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4">{{ old('description', $listing->description) }}</textarea>
                    @error('description') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" {{ $listing->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $listing->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.listings') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">Save Changes</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>
