<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Add Exchange</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

       @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Add Exchange</h1>
                <p>Manually create a skill exchange record.</p>
            </div>
        </header>

        <section class="users-page">
            <form class="settings-form" method="POST" action="{{ route('admin.exchanges.store') }}">
                @csrf

                <div class="settings-field">
                    <label for="provider_id">Provider</label>
                    <select id="provider_id" name="provider_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('provider_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="learner_id">Learner</label>
                    <select id="learner_id" name="learner_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('learner_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="skill_offered">Skill the provider teaches</label>
                    <input id="skill_offered" name="skill_offered" type="text" value="{{ old('skill_offered') }}">
                    @error('skill_offered') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="skill_wanted">Skill the learner teaches back (optional)</label>
                    <input id="skill_wanted" name="skill_wanted" type="text" value="{{ old('skill_wanted') }}">
                    <p class="ll-muted">Leave empty for a learner-only exchange, where the provider helps and nothing is swapped back.</p>
                    @error('skill_wanted') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

<<<<<<< HEAD
                
<div class="settings-field">
    <label>Initial Status</label>
    <p style="margin:0; padding:10px 12px; border-radius:8px;
              background:#f3f4f6; color:#374151;">
        Pending
    </p>
    <small style="display:block; margin-top:6px; color:#69766e;">
        The exchange must progress through the defined status workflow.
    </small>
</div>

=======
                <div class="settings-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pending">Pending</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="disputed">Disputed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
>>>>>>> 7128c7a35fdb01d62338eda557a779da41476150

                <div class="settings-actions">
                    <a href="{{ route('admin.exchanges') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">Create Exchange</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>