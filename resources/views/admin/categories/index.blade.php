@extends('layouts.admin')

@section('title', 'Categories')
@section('page-title', 'Skill Categories')
@section('page-subtitle', 'Organize the skills users can offer and request.')

@section('content')

<section class="admin-page">

    <!-- ADD NEW CATEGORY -->
    <div class="detail-section">

        <h2 class="section-title">Add new category</h2>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="inline-add-form">
            @csrf
            <input type="text" name="name" placeholder="e.g. Programming Language" required>
            <button type="submit" class="btn-primary">+ Add Category</button>
        </form>

    </div>


    <!-- CATEGORY TREE -->
    <div class="category-tree">

        @forelse ($categories as $category)
            <div class="category-block">

                <!-- CATEGORY HEADER -->
                <div class="category-header">

                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="category-name-form">
                        @csrf @method('PATCH')
                        <input type="text" name="name" value="{{ $category->name }}" class="category-name-input">
                        <button type="submit" class="btn-small">Save</button>
                    </form>

                    <div class="category-header-right">
                        <span class="cell-muted">{{ $category->skills_count }} skill{{ $category->skills_count === 1 ? '' : 's' }}</span>

                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                              onsubmit="return confirm('Delete &quot;{{ $category->name }}&quot; and all its skills?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-small btn-danger">Delete category</button>
                        </form>
                    </div>

                </div>


                <!-- SKILLS UNDER THIS CATEGORY -->
                <div class="skill-list">

                    @forelse ($category->skills as $skill)
                        <div class="skill-row">

                            <span class="skill-branch">&#9492;&#9472;</span>

                            <form method="POST" action="{{ route('admin.skills.update', $skill) }}" class="skill-name-form">
                                @csrf @method('PATCH')
                                <input type="text" name="name" value="{{ $skill->name }}" class="skill-name-input">
                                <button type="submit" class="btn-small">Save</button>
                            </form>

                            <form method="POST" action="{{ route('admin.skills.destroy', $skill) }}"
                                  onsubmit="return confirm('Remove &quot;{{ $skill->name }}&quot;?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-small btn-danger">Remove</button>
                            </form>

                        </div>
                    @empty
                        <p class="empty-note skill-empty">No skills under this category yet.</p>
                    @endforelse

                    <!-- ADD SKILL -->
                    <form method="POST" action="{{ route('admin.categories.skills.store', $category) }}" class="skill-row skill-add-row">
                        @csrf
                        <span class="skill-branch">&#9492;&#9472;</span>
                        <input type="text" name="name" placeholder="Add a skill..." class="skill-name-input" required>
                        <button type="submit" class="btn-small btn-add">+ Add</button>
                    </form>

                </div>

            </div>
        @empty
            <p class="empty-note">No categories yet. Add one above to get started.</p>
        @endforelse

    </div>

</section>

@endsection
