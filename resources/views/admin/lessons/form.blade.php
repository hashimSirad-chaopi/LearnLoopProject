<?php $editing = $lesson->exists; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - {{ $editing ? 'Edit' : 'Add' }} Lesson</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>{{ $editing ? 'Edit Lesson' : 'Add Lesson' }}</h1>
                <p>{{ $skill->icon }} {{ $skill->title }}</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <form class="ll-card ll-form" method="POST" enctype="multipart/form-data"
                          action="{{ $editing ? route('admin.skills.lessons.update', [$skill, $lesson]) : route('admin.skills.lessons.store', $skill) }}">
                        @csrf
                        @if($editing) @method('PUT') @endif

                        <div>
                            <label for="title">Lesson title</label>
                            <input id="title" name="title" type="text" value="{{ old('title', $lesson->title) }}" required>
                        </div>

                        <div>
                            <label for="level">Level</label>
                            <select id="level" name="level">
                                @foreach($levels as $level)
                                    <option value="{{ $level }}" {{ old('level', $levelName) === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                                @endforeach
                            </select>
                            <p class="ll-muted">Learners go through Easy, Moderate, Hard, then Expert.</p>
                        </div>

                        <?php $ctype = old('content_type', $lesson->content_type ?: 'text'); ?>
                        <div>
                            <label for="content_type">Lesson type</label>
                            <select id="content_type" name="content_type">
                                @foreach(['text' => 'Text', 'file' => 'File (PDF, document, slides)', 'audio' => 'Audio', 'video' => 'Video', 'photo' => 'Photo'] as $value => $label)
                                    <option value="{{ $value }}" {{ $ctype === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="ll-muted">Choose how the lesson is delivered. The quiz below works the same for every type.</p>
                            @error('content_type')<p class="ll-error">{{ $message }}</p>@enderror
                        </div>

                        <div id="attachment-box" style="display:none;">
                            <label for="attachment">Upload</label>
                            <input id="attachment" name="attachment" type="file">
                            <p class="ll-muted" id="attachment-hint"></p>
                            @if($editing && $lesson->attachment_path)
                                <p class="ll-muted">Current file: <a href="{{ $lesson->attachment_url }}" target="_blank" rel="noopener">{{ $lesson->attachment_name ?: 'open' }}</a>. Upload a new one to replace it.</p>
                            @endif
                            @error('attachment')<p class="ll-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="content" id="content-label">Lesson text (shown before the quiz)</label>
                            <textarea id="content" name="content" rows="8">{{ old('content', $lesson->content) }}</textarea>
                            @error('content')<p class="ll-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label>Quiz questions</label>
                            <p class="ll-muted">Four answer options per question. You can put a photo, video or audio clip above any question. Select the circle next to the correct one. The pass mark in the app is 70%.</p>

                            <div id="questions">
                                @foreach($questions as $qi => $q)
                                    <div class="ll-question" data-question>
                                        <div class="ll-row between">
                                            <strong data-qnum>Question {{ $loop->iteration }}</strong>
                                            <button type="button" class="delete-btn" data-remove>Remove</button>
                                        </div>
                                        <div style="margin-top:8px;">
                                            <input type="text" data-field="text" name="questions[{{ $qi }}][text]" value="{{ $q['text'] ?? '' }}" placeholder="Question text">
                                        </div>
                                        <div style="margin-top:8px;">
                                            <label style="font-weight:600;font-size:13px;">Photo, video or audio above this question (optional)</label>
                                            <?php $keep = $q['keep_media'] ?? ($q['media_path'] ?? null); $mtype = $q['media_type'] ?? null; $murl = $q['media_url'] ?? ($keep ? asset('storage/'.$keep) : null); ?>
                                            @if($keep && $murl)
                                                <div style="margin:6px 0;">
                                                    @if($mtype === 'photo') <img src="{{ $murl }}" alt="" style="max-height:120px;border-radius:8px;">
                                                    @elseif($mtype === 'video') <video src="{{ $murl }}" controls style="max-height:140px;border-radius:8px;"></video>
                                                    @elseif($mtype === 'audio') <audio src="{{ $murl }}" controls></audio>
                                                    @endif
                                                    <input type="hidden" data-field="keep_media" name="questions[{{ $qi }}][keep_media]" value="{{ $keep }}">
                                                    <label style="display:block;font-size:12.5px;"><input type="checkbox" data-field="remove_media" name="questions[{{ $qi }}][remove_media]" value="1"> Remove this file</label>
                                                </div>
                                            @endif
                                            <input type="file" data-field="media" name="questions[{{ $qi }}][media]" accept="image/*,video/*,audio/*">
                                            @error("questions.$qi.media") <div class="ll-error">{{ $message }}</div> @enderror
                                        </div>
                                        @for($oi = 0; $oi < 4; $oi++)
                                            <div class="ll-option">
                                                <input type="radio" data-field="correct" name="questions[{{ $qi }}][correct]" value="{{ $oi }}" {{ (int) ($q['correct'] ?? 0) === $oi ? 'checked' : '' }}>
                                                <input type="text" data-field="option" data-oi="{{ $oi }}" name="questions[{{ $qi }}][options][{{ $oi }}]" value="{{ $q['options'][$oi] ?? '' }}" placeholder="Option {{ $oi + 1 }}">
                                            </div>
                                        @endfor
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" class="table-btn" id="add-question">+ Add question</button>
                        </div>

                        <div class="settings-actions">
                            <a href="{{ route('admin.skills.show', $skill) }}" class="table-btn ll-link">Cancel</a>
                            <button type="submit" class="settings-save-btn">{{ $editing ? 'Save Lesson' : 'Add Lesson' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>
</div>

{{-- A blank question the "+ Add question" button copies. __I__ becomes the next index. --}}
<template id="question-template">
    <div class="ll-question" data-question>
        <div class="ll-row between">
            <strong data-qnum>Question</strong>
            <button type="button" class="delete-btn" data-remove>Remove</button>
        </div>
        <div style="margin-top:8px;">
            <input type="text" data-field="text" name="questions[__I__][text]" placeholder="Question text">
        </div>
        <div style="margin-top:8px;">
            <label style="font-weight:600;font-size:13px;">Photo, video or audio above this question (optional)</label>
            <input type="file" data-field="media" name="questions[__I__][media]" accept="image/*,video/*,audio/*">
        </div>
        @for($oi = 0; $oi < 4; $oi++)
            <div class="ll-option">
                <input type="radio" data-field="correct" name="questions[__I__][correct]" value="{{ $oi }}" {{ $oi === 0 ? 'checked' : '' }}>
                <input type="text" data-field="option" data-oi="{{ $oi }}" name="questions[__I__][options][{{ $oi }}]" placeholder="Option {{ $oi + 1 }}">
            </div>
        @endfor
    </div>
</template>

<script>
(function () {
    var list = document.getElementById('questions');
    var tpl = document.getElementById('question-template');

    // Rename every field so the indexes are 0,1,2... with no gaps.
    function renumber() {
        var cards = list.querySelectorAll('[data-question]');
        cards.forEach(function (card, i) {
            card.querySelector('[data-qnum]').textContent = 'Question ' + (i + 1);
            card.querySelectorAll('[data-field]').forEach(function (el) {
                var kind = el.getAttribute('data-field');
                if (kind === 'text') el.name = 'questions[' + i + '][text]';
                if (kind === 'correct') el.name = 'questions[' + i + '][correct]';
                if (kind === 'media') el.name = 'questions[' + i + '][media]';
                if (kind === 'keep_media') el.name = 'questions[' + i + '][keep_media]';
                if (kind === 'remove_media') el.name = 'questions[' + i + '][remove_media]';
                if (kind === 'option') el.name = 'questions[' + i + '][options][' + el.getAttribute('data-oi') + ']';
            });
        });
    }

    document.getElementById('add-question').addEventListener('click', function () {
        if (list.querySelectorAll('[data-question]').length >= 10) { alert('A lesson can have up to 10 questions.'); return; }
        list.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__I__/g, '0'));
        renumber();
    });

    list.addEventListener('click', function (e) {
        if (!e.target.hasAttribute('data-remove')) return;
        if (list.querySelectorAll('[data-question]').length <= 1) { alert('A lesson needs at least one question.'); return; }
        e.target.closest('[data-question]').remove();
        renumber();
    });

    renumber();

    // The lesson type decides whether there is an upload, and what it accepts.
    var typeSelect = document.getElementById('content_type');
    var box = document.getElementById('attachment-box');
    var input = document.getElementById('attachment');
    var hint = document.getElementById('attachment-hint');
    var label = document.getElementById('content-label');
    var rules = {
        file:  { accept: '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.csv', hint: 'PDF, Word, PowerPoint, Excel, text, CSV or ZIP. Up to 20 MB.' },
        audio: { accept: 'audio/*', hint: 'MP3, M4A, AAC, WAV or OGG. Up to 30 MB.' },
        video: { accept: 'video/*', hint: 'MP4, MOV, M4V or WEBM. Up to 50 MB.' },
        photo: { accept: 'image/*', hint: 'JPG, PNG or WEBP. Up to 10 MB.' }
    };
    function syncType() {
        var t = typeSelect.value, r = rules[t];
        box.style.display = r ? '' : 'none';
        if (r) { input.accept = r.accept; hint.textContent = r.hint; }
        label.textContent = t === 'text' ? 'Lesson text (shown before the quiz)' : 'Short note for the learner (optional)';
        document.getElementById('content').required = (t === 'text');
    }
    typeSelect.addEventListener('change', syncType);
    syncType();
})();
</script>

</body>
</html>
