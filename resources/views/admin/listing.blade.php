<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnLoop - Listings</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

<div class="admin-container">

    <!-- =========================
         SIDEBAR
         ========================= -->

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


    <!-- =========================
         MAIN CONTENT
         ========================= -->

    <main class="main-content">


        <!-- =========================
             FIXED TOP HEADER
             ========================= -->

        <header class="top-header">

            <h1>Listings</h1>

            <p>
                Review and manage listings posted by LearnLoop users.
            </p>

        </header>


        <!-- =========================
             LISTING PAGE
             ========================= -->

        <section class="listing-page">


            <!-- =========================
                 SEARCH BAR
                 FIXED ABOVE LISTINGS
                 ========================= -->

            <div class="listing-toolbar">

                <input
                    type="text"
                    class="listing-search"
                    placeholder="Search listings..."
                >

            </div>


            <!-- =========================
                 ONLY THIS PART SCROLLS
                 ========================= -->

            <div class="listings-scroll-area">

                <div class="listings-feed">


                    <!-- LISTING 1 -->

                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-avatar">
                                HC
                            </div>

                            <div class="listing-user">

                                <h3>Harold Caño</h3>

                                <p>Posted recently</p>

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
                                I can help students understand basic
                                mathematics, algebra, and problem solving.
                            </p>

                        </div>


                        <div class="listing-post-footer">

                            <span class="listing-date">
                                LearnLoop Listing
                            </span>

                            <div class="listing-actions">

                                <button class="view-btn">
                                    View
                                </button>

                                <button class="approve-btn">
                                    Approve
                                </button>

                                <button class="disable-btn">
                                    Disable
                                </button>

                                <button class="delete-btn">
                                    Delete
                                </button>

                            </div>

                        </div>

                    </article>



                    <!-- LISTING 2 -->

                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-avatar">
                                MB
                            </div>

                            <div class="listing-user">

                                <h3>Myra Balaba</h3>

                                <p>Posted recently</p>

                            </div>

                            <span class="listing-status pending">
                                Pending
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
                                Looking for students who want to improve
                                their English communication and speaking skills.
                            </p>

                        </div>


                        <div class="listing-post-footer">

                            <span class="listing-date">
                                LearnLoop Listing
                            </span>

                            <div class="listing-actions">

                                <button class="view-btn">
                                    View
                                </button>

                                <button class="approve-btn">
                                    Approve
                                </button>

                                <button class="disable-btn">
                                    Disable
                                </button>

                                <button class="delete-btn">
                                    Delete
                                </button>

                            </div>

                        </div>

                    </article>



                    <!-- LISTING 3 -->

                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-avatar">
                                RF
                            </div>

                            <div class="listing-user">

                                <h3>Richard Formoso</h3>

                                <p>Posted recently</p>

                            </div>

                            <span class="listing-status active">
                                Active
                            </span>

                        </div>


                        <div class="listing-post-content">

                            <span class="listing-category">
                                Programming
                            </span>

                            <h2>
                                Beginner Programming Tutor
                            </h2>

                            <p>
                                Offering tutoring sessions for beginners
                                who want to learn programming fundamentals.
                            </p>

                        </div>


                        <div class="listing-post-footer">

                            <span class="listing-date">
                                LearnLoop Listing
                            </span>

                            <div class="listing-actions">

                                <button class="view-btn">
                                    View
                                </button>

                                <button class="approve-btn">
                                    Approve
                                </button>

                                <button class="disable-btn">
                                    Disable
                                </button>

                                <button class="delete-btn">
                                    Delete
                                </button>

                            </div>

                        </div>

                    </article>



                    <!-- LISTING 4 -->

                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-avatar">
                                CJ
                            </div>

                            <div class="listing-user">

                                <h3>Christian Jay Ayunan</h3>

                                <p>Posted recently</p>

                            </div>

                            <span class="listing-status inactive">
                                Inactive
                            </span>

                        </div>


                        <div class="listing-post-content">

                            <span class="listing-category">
                                Science
                            </span>

                            <h2>
                                Science Study Assistance
                            </h2>

                            <p>
                                Providing study assistance for students
                                who need help with science subjects.
                            </p>

                        </div>


                        <div class="listing-post-footer">

                            <span class="listing-date">
                                LearnLoop Listing
                            </span>

                            <div class="listing-actions">

                                <button class="view-btn">
                                    View
                                </button>

                                <button class="approve-btn">
                                    Approve
                                </button>

                                <button class="disable-btn">
                                    Disable
                                </button>

                                <button class="delete-btn">
                                    Delete
                                </button>

                            </div>

                        </div>

                    </article>


                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>