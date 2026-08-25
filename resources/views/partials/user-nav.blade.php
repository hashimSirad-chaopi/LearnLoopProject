<nav class="navbar">
    <div class="logo">LearnLoop</div>

    <input type="checkbox" id="menu-toggle" class="menu-toggle">
    <label for="menu-toggle" class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </label>

    <ul class="nav-links">
        <li class="list {{ request()->routeIs('user.home') ? 'active' : '' }}">
            <a href="{{ route('user.home') }}">Browse</a>
        </li>
        <li class="list {{ request()->routeIs('matches') ? 'active' : '' }}">
            <a href="{{ route('matches') }}">Matches</a>
        </li>
        <li class="list {{ request()->routeIs('exchanges') ? 'active' : '' }}">
            <a href="{{ route('exchanges') }}">Exchanges</a>
        </li>
        <li class="list {{ request()->routeIs('messages') ? 'active' : '' }}">
            <a href="{{ route('messages') }}">Messages</a>
        </li>
        <li class="list {{ request()->routeIs('profile') ? 'active' : '' }}">
            <a href="{{ route('profile') }}">Profile</a>
        </li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </li>
    </ul>
</nav>