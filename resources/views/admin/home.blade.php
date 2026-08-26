<div id="homecontainer">

    <nav class="navbar">
        <div class="logo">LearnLoop</div>

        <input type="checkbox" id="menu-toggle" class="menu-toggle">

        <label for="menu-toggle" class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </label>

        <ul class="nav-links">
            <li class="list"><a href="#">Dashboard</a></li>
            <li class="list"><a href="#">User Management</a></li>
            <li class="list"><a href="#">Listings</a></li>
            <li class="list"><a href="#">Reports</a></li>
            <li class="list"><a href="#">Settings</a></li>

            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Log Out</button>
                </form>
            </li>
        </ul>
    </nav>


    <main class="main-content">

        <h1>DASHBOARD</h1>

        <div class="cards">

            <div class="card">
                <h2>{{ \App\Models\User::count() }}</h2>
                <p>Total Users</p>
            </div>

            <div class="card">
                <h2>10</h2>
                <p>Active Tutors</p>
            </div>

            <div class="card">
                <h2>3</h2>
                <p>Active Tutoring Sessions</p>
            </div>

            <div class="card">
                <h2>3453</h2>
                <p>Average User Rating</p>
            </div>

        </div>

    </main>

</div>