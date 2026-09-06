<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnLoop - User Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body>

<div class="admin-container">


    <!-- =========================================
         SIDEBAR
         ========================================= -->

    <aside class="sidebar">

        <div class="logo">
            LearnLoop
        </div>


        <nav class="sidebar-nav">

            <a href="{{ route('admin.home') }}" class="nav-item">
                Dashboard
            </a>

            <a href="{{ route('admin.users') }}" class="nav-item active">
                User Management
            </a>

            <a href="{{ route('admin.listings') }}" class="nav-item">
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



    <!-- =========================================
         MAIN CONTENT
         ========================================= -->

    <main class="main-content">


        <!-- =====================================
             HEADER
             ===================================== -->

        <header class="top-header">

            <div>

                <h1>User Management</h1>

                <p>
                    Manage LearnLoop users and their accounts.
                </p>

            </div>

        </header>



        <!-- =====================================
             USERS PAGE
             ===================================== -->

        <section class="users-page">


            <!-- =================================
                 FIXED USERS HEADER + SEARCH
                 ================================= -->

            <div class="users-toolbar">


                <!-- PAGE TITLE -->

                <div class="dashboard-heading">

                    <h2>
                        Users
                    </h2>

                    <p>
                        View and manage registered users.
                    </p>

                </div>


                <!-- SEARCH -->

                <div class="user-tools">

                    <input
                        type="text"
                        class="user-search"
                        placeholder="Search users..."
                    >

                </div>


            </div>



            <!-- =================================
                 SCROLLABLE AREA
                 ================================= -->

            <div class="users-scroll-area">


                <!-- =================================
                     USER TABLE
                     ================================= -->

                <div class="users-table-container">


                    <table class="users-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>



                        <!-- TABLE BODY -->

                        <tbody>


                            @foreach($users as $user)


                                <tr>


                                    <!-- NAME -->

                                    <td>

                                        {{ $user->name }}

                                    </td>



                                    <!-- EMAIL -->

                                    <td>

                                        {{ $user->email }}

                                    </td>



                                    <!-- ROLE -->

                                    <td>

                                        <span class="role-badge">

                                            {{ $user->role ?? 'User' }}

                                        </span>

                                    </td>



                                    <!-- STATUS -->

                                    <td>

                                        <span class="status-badge active-status">

                                            Active

                                        </span>

                                    </td>



                                    <!-- ACTION -->

                                    <td class="action-buttons">


                                        <!-- VIEW -->

                                        <button
                                            type="button"
                                            class="table-btn"
                                        >
                                            View
                                        </button>



                                        <!-- DELETE -->

                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.delete', $user->id) }}"
                                            onsubmit="return confirm('Are you sure you want to delete {{ $user->name }}?');"
                                        >

                                            @csrf

                                            @method('DELETE')


                                            <button
                                                type="submit"
                                                class="delete-btn"
                                            >
                                                Delete
                                            </button>

                                        </form>


                                    </td>


                                </tr>


                            @endforeach


                        </tbody>


                    </table>


                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>