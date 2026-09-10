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

    <!-- =========================================
         SIDEBAR
    ========================================== -->
    <aside class="sidebar">

        <div class="sidebar-top">

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

        </div>


        <!-- LOG OUT -->
        <div class="sidebar-bottom">

            <form method="POST" action="{{ route('logout') }}">

                @csrf

                <button type="submit" class="logout-btn">
                    Log Out
                </button>

            </form>

        </div>

    </aside>


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->
    <main class="main-content">

        <!-- FIXED HEADER -->
        <header class="top-header">

            <div class="header-left">

                <span class="header-label">
                    ADMIN PANEL
                </span>

                <h1>
                    Listings
                </h1>

                <p>
                    Review and manage listings posted by LearnLoop users.
                </p>

            </div>

        </header>


        <!-- =====================================
             LISTINGS PAGE
        ====================================== -->
        <section class="listing-page">

            <!-- TOOLBAR -->
            <div class="listing-toolbar">

                <div class="dashboard-heading">

                    <div>

                        <span class="section-label">
                            LISTINGS
                        </span>

                        <h2>
                            All Listings
                        </h2>

                        <p>
                            Review, disable, or remove user-created listings.
                        </p>

                    </div>

                </div>


                <!-- SEARCH -->
                <input
                    type="search"
                    class="listing-search"
                    placeholder="Search listings..."
                    aria-label="Search listings"
                >

            </div>


            <!-- =================================
                 SCROLLABLE LISTINGS
            ================================== -->
            <div class="listings-scroll-area">

                <div class="listings-feed">


                    <!-- =================================
                         LISTING 1
                    ================================== -->
                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-user">

                                <div class="listing-avatar">
                                    HC
                                </div>

                                <div>
                                    <h3>
                                        Harold Caño
                                    </h3>

                                    <span>
                                        Mathematics
                                    </span>
                                </div>

                            </div>

                            <span class="listing-status active">
                                Active
                            </span>

                        </div>


                        <div class="listing-content">

                            <span class="listing-category">
                                Mathematics
                            </span>

                            <h2>
                                Mathematics Tutoring
                            </h2>

                            <p>
                                I can help students understand basic math,
                                algebra, problem solving, and other
                                mathematics topics.
                            </p>

                        </div>


                        <div class="listing-footer">

                            <span class="listing-date">
                                Posted recently
                            </span>


                            <div class="listing-actions">

                                <button
                                    type="button"
                                    class="view-btn"
                                >
                                    View
                                </button>

                                <button
                                    type="button"
                                    class="disable-btn"
                                >
                                    Disable
                                </button>

                                <button
                                    type="button"
                                    class="delete-btn"
                                >
                                    Delete
                                </button>

                            </div>

                        </div>

                    </article>



                    <!-- =================================
                         LISTING 2
                    ================================== -->
                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-user">

                                <div class="listing-avatar">
                                    MB
                                </div>

                                <div>
                                    <h3>
                                        Myra Balaba
                                    </h3>

                                    <span>
                                        English
                                    </span>
                                </div>

                            </div>

                            <span class="listing-status pending">
                                Pending
                            </span>

                        </div>


                        <div class="listing-content">

                            <span class="listing-category">
                                English
                            </span>

                            <h2>
                                English Conversation Practice
                            </h2>

                            <p>
                                Practice English communication and speaking
                                skills through friendly conversation sessions.
                            </p>

                        </div>


                        <div class="listing-footer">

                            <span class="listing-date">
                                Posted recently
                            </span>


                            <div class="listing-actions">

                                <button
                                    type="button"
                                    class="view-btn"
                                >
                                    View
                                </button>

                                <button
                                    type="button"
                                    class="disable-btn"
                                >
                                    Disable
                                </button>

                                <button
                                    type="button"
                                    class="delete-btn"
                                >
                                    Delete
                                </button>

                            </div>

                        </div>

                    </article>



                    <!-- =================================
                         LISTING 3
                    ================================== -->
                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-user">

                                <div class="listing-avatar">
                                    RF
                                </div>

                                <div>
                                    <h3>
                                        Richard Formoso
                                    </h3>

                                    <span>
                                        Programming
                                    </span>
                                </div>

                            </div>

                            <span class="listing-status active">
                                Active
                            </span>

                        </div>


                        <div class="listing-content">

                            <span class="listing-category">
                                Programming
                            </span>

                            <h2>
                                Beginner Programming Tutor
                            </h2>

                            <p>
                                Learn programming fundamentals and improve
                                your understanding of basic coding concepts.
                            </p>

                        </div>


                        <div class="listing-footer">

                            <span class="listing-date">
                                Posted recently
                            </span>


                            <div class="listing-actions">

                                <button
                                    type="button"
                                    class="view-btn"
                                >
                                    View
                                </button>

                                <button
                                    type="button"
                                    class="disable-btn"
                                >
                                    Disable
                                </button>

                                <button
                                    type="button"
                                    class="delete-btn"
                                >
                                    Delete
                                </button>

                            </div>

                        </div>

                    </article>



                    <!-- =================================
                         LISTING 4
                    ================================== -->
                    <article class="listing-post">

                        <div class="listing-post-header">

                            <div class="listing-user">

                                <div class="listing-avatar">
                                    CJ
                                </div>

                                <div>
                                    <h3>
                                        Christian Jay Ayunan
                                    </h3>

                                    <span>
                                        Science
                                    </span>
                                </div>

                            </div>

                            <span class="listing-status inactive">
                                Inactive
                            </span>

                        </div>


                        <div class="listing-content">

                            <span class="listing-category">
                                Science
                            </span>

                            <h2>
                                Science Study Assistance
                            </h2>

                            <p>
                                Get assistance with different science
                                subjects and improve your understanding
                                through guided study sessions.
                            </p>

                        </div>


                        <div class="listing-footer">

                            <span class="listing-date">
                                Posted recently
                            </span>


                            <div class="listing-actions">

                                <button
                                    type="button"
                                    class="view-btn"
                                >
                                    View
                                </button>

                                <button
                                    type="button"
                                    class="disable-btn"
                                >
                                    Disable
                                </button>

                                <button
                                    type="button"
                                    class="delete-btn"
                                >
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