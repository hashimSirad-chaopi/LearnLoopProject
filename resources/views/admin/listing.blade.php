<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

```
<title>LearnLoop - Listings</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])
```

</head>

<body>

<div class="admin-container">

```
<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="logo">
        LearnLoop
    </div>

    <nav class="sidebar-nav">

        <a href="{{ route('admin.home') }}" class="nav-item">
    Dashboard
</a>

<a href="{{ route('admin.users') }}" class="nav-item">
    User Management
</a>

<a href="{{ route('admin.listings') }}" class="nav-item active">
    Listings
</a>

<a href="{{ route('admin.reports') }}" class="nav-item">
    Reports
</a>

<a href="{{ route('admin.settings') }}" class="nav-item">
    Settings
</a>

    </nav>

    <div class="sidebar-bottom">

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="logout-btn">
                Log Out
            </button>
        </form>

    </div>

</aside>


<!-- MAIN CONTENT -->
<main class="main-content">

    <!-- HEADER -->
    <header class="top-header">

        <div>
            <h1>Listings</h1>

            <p>
                Review and manage listings posted by LearnLoop users.
            </p>
        </div>

    </header>


    <!-- CONTENT -->
    <section class="dashboard-content">

        <div class="dashboard-heading">

            <h2>All Listings</h2>

            <p>
                Monitor, review, and manage user-created listings.
            </p>

        </div>


        <!-- SEARCH AND FILTER -->
        <div class="listing-tools">

            <input
                type="text"
                class="listing-search"
                placeholder="Search listings..."
            >

            <select class="listing-filter">

                <option value="all">
                    All
                </option>

                <option value="active">
                    Active
                </option>

                <option value="pending">
                    Pending
                </option>

                <option value="disabled">
                    Disabled
                </option>

            </select>

        </div>


        <!-- LISTINGS FEED -->
        <div class="listings-feed">


            <!-- LISTING 1 -->
            <article class="listing-post">

                <div class="listing-post-header">

                    <div class="listing-avatar">
                        H
                    </div>

                    <div class="listing-user">

                        <h3>
                            Harold Caño
                        </h3>

                        <span>
                            Posted recently
                        </span>

                    </div>

                    <span class="listing-status active">
                        Active
                    </span>

                </div>


                <div class="listing-post-content">

                    <span class="listing-category">
                        Mathematics
                    </span>

                    <h2>
                        Mathematics Tutoring
                    </h2>

                    <p>
                        I can help students with basic mathematics,
                        algebra, and other math-related topics.
                    </p>

                </div>


                <div class="listing-post-footer">

                    <span>
                        Tutoring
                    </span>

                    <div class="listing-actions">

                        <button class="listing-view-btn">
                            View
                        </button>

                        <button class="listing-approve-btn">
                            Approve
                        </button>

                        <button class="listing-disable-btn">
                            Disable
                        </button>

                        <button class="listing-delete-btn">
                            Delete
                        </button>

                    </div>

                </div>

            </article>


            <!-- LISTING 2 -->
            <article class="listing-post">

                <div class="listing-post-header">

                    <div class="listing-avatar">
                        M
                    </div>

                    <div class="listing-user">

                        <h3>
                            Myra Balaba
                        </h3>

                        <span>
                            Posted recently
                        </span>

                    </div>

                    <span class="listing-status active">
                        Active
                    </span>

                </div>


                <div class="listing-post-content">

                    <span class="listing-category">
                        English
                    </span>

                    <h2>
                        English Conversation Practice
                    </h2>

                    <p>
                        Looking for someone to practice English
                        conversation and improve communication skills.
                    </p>

                </div>


                <div class="listing-post-footer">

                    <span>
                        Skill Exchange
                    </span>

                    <div class="listing-actions">

                        <button class="listing-view-btn">
                            View
                        </button>

                        <button class="listing-approve-btn">
                            Approve
                        </button>

                        <button class="listing-disable-btn">
                            Disable
                        </button>

                        <button class="listing-delete-btn">
                            Delete
                        </button>

                    </div>

                </div>

            </article>


            <!-- LISTING 3 -->
            <article class="listing-post">

                <div class="listing-post-header">

                    <div class="listing-avatar">
                        R
                    </div>

                    <div class="listing-user">

                        <h3>
                            Richard Formoso
                        </h3>

                        <span>
                            Posted recently
                        </span>

                    </div>

                    <span class="listing-status pending">
                        Pending
                    </span>

                </div>


                <div class="listing-post-content">

                    <span class="listing-category">
                        Programming
                    </span>

                    <h2>
                        Web Development Help
                    </h2>

                    <p>
                        Looking for someone who can help with
                        HTML, CSS, JavaScript, and Laravel.
                    </p>

                </div>


                <div class="listing-post-footer">

                    <span>
                        Tutoring
                    </span>

                    <div class="listing-actions">

                        <button class="listing-view-btn">
                            View
                        </button>

                        <button class="listing-approve-btn">
                            Approve
                        </button>

                        <button class="listing-disable-btn">
                            Disable
                        </button>

                        <button class="listing-delete-btn">
                            Delete
                        </button>

                    </div>

                </div>

            </article>


            <!-- LISTING 4 -->
            <article class="listing-post">

                <div class="listing-post-header">

                    <div class="listing-avatar">
                        C
                    </div>

                    <div class="listing-user">

                        <h3>
                            Christian Jay Ayunan
                        </h3>

                        <span>
                            Posted recently
                        </span>

                    </div>

                    <span class="listing-status inactive">
                        Disabled
                    </span>

                </div>


                <div class="listing-post-content">

                    <span class="listing-category">
                        Computer Skills
                    </span>

                    <h2>
                        Basic Computer Lessons
                    </h2>

                    <p>
                        Offering basic computer lessons for beginners
                        who want to learn about computer applications.
                    </p>

                </div>


                <div class="listing-post-footer">

                    <span>
                        Skill Exchange
                    </span>

                    <div class="listing-actions">

                        <button class="listing-view-btn">
                            View
                        </button>

                        <button class="listing-approve-btn">
                            Approve
                        </button>

                        <button class="listing-disable-btn">
                            Disable
                        </button>

                        <button class="listing-delete-btn">
                            Delete
                        </button>

                    </div>

                </div>

            </article>


        </div>

    </section>

</main>
```

</div>

</body>
</html>
