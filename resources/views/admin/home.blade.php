
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnLoop - Admin Dashboard</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

<div class="admin-container">

    <!-- =========================================
         SIDEBAR
         ========================================= -->
       @include('partials.admin-sidebar')


    <!-- =========================================
         MAIN CONTENT
         ========================================= -->
    <main class="main-content">


        <!-- =====================================
             FIXED TOP HEADER
             ===================================== -->
        <header class="top-header">

            <div class="header-left">

                <span class="header-label">
                    ADMIN PANEL
                </span>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Welcome to your LearnLoop administration panel.
                </p>

            </div>

        </header>


        <!-- =====================================
             DASHBOARD CONTENT
             ===================================== -->
        <section class="dashboard-content">


            <!-- =================================
                 OVERVIEW
                 ================================= -->
            <div class="dashboard-heading">

                <div>
                    <span class="section-label">
                        OVERVIEW
                    </span>

                    <h2>
                        System Overview
                    </h2>

                    <p>
                        Monitor the current activity and statistics of LearnLoop.
                    </p>
                </div>

            </div>


            <!-- =================================
                 STATISTICS
                 ================================= -->
            <div class="cards">


                <!-- TOTAL USERS -->
                <div class="card">

                    <div class="card-top">
                        <span class="card-label">
                            TOTAL USERS
                        </span>
                    </div>

                    <h2>
                        {{ \App\Models\User::count() }}
                    </h2>

                    <p class="card-description">
                        Registered users
                    </p>

                </div>


                <!-- ACTIVE TUTORS -->
<div class="card">
    <div class="card-top">
        <span class="card-label">ACTIVE TUTORS</span>
    </div>
    <h2>{{ $activeTutors }}</h2>
    <p class="card-description">Currently active tutors</p>
</div>


                <!-- ACTIVE SESSIONS -->
               <!-- ACTIVE SESSIONS -->
<div class="card">
    <div class="card-top">
        <span class="card-label">ACTIVE SESSIONS</span>
    </div>
    <h2>{{ $activeSessions }}</h2>
    <p class="card-description">Ongoing tutoring sessions</p>
</div>


                <!-- AVERAGE RATING -->
                <div class="card">

                    <div class="card-top">
                        <span class="card-label">
                            AVERAGE RATING
                        </span>
                    </div>

                    <h2>
                        No data available yet
                    </h2>

                    <p class="card-description">
                        Overall user rating
                    </p>

                </div>

            </div>


            <!-- =================================
                 RECENT ACTIVITY
                 ================================= -->
            <div class="recent-activity">

                <div class="activity-header">

                    <div>
                        <span class="section-label">
                            ACTIVITY
                        </span>

                        <h2>
                            Recent Activity
                        </h2>
                    </div>

                </div>


                @forelse($activities as $activity)
    <div class="activity-item">
        <div class="activity-indicator"></div>
        <div class="activity-text">
            <strong>{{ $activity['text'] }}</strong>
            <p>{{ $activity['time']->diffForHumans() }}</p>
        </div>
    </div>
@empty
    <div class="activity-item">
        <div class="activity-indicator"></div>
        <div class="activity-text">
            <strong>No recent activity</strong>
            <p>There is currently no recent administrative activity to display.</p>
        </div>
    </div>
@endforelse

            </div>


        </section>

    </main>

</div>

</body>
</html>

