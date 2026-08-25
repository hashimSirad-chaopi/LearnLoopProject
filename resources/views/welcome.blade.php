{{--
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LearnLoop</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<!-- LOGIN PAGE -->
<div id="page-login" class="page-section" style="display:block;">
    <div id="container">
        <h1>LEARN LOOP</h1>
        <h3>LOG IN</h3>
        <br>

        <input type="text" id="login-username" placeholder="Username">
        <br>
        <input type="password" id="login-password" placeholder="password">
        <br>
        <button onclick="handleLogin()">Login</button>

        <br>
        <br>
        <button onclick="showPage('register')">Register</button>
    </div>
</div>

<!-- REGISTER PAGE -->
<div id="page-register" class="page-section" style="display:none;">
    <div id="container">
        <h1>LEARN LOOP</h1>
        <h3>REGISTER</h3>
        <br>

        <input type="text" id="register-username" placeholder="Username">
        <br>
        <input type="email" id="register-email" placeholder="Email">
        <br>
        <input type="password" id="register-password" placeholder="password">
        <br>

        <br>
        <br>
        <button onclick="showPage('login')">Back to Login</button>
        <br>
        <button onclick="showPage('login')">Register</button>
    </div>
</div>

<!-- ADMIN HOME -->
<div id="page-ADMINHOME" class="page-section" style="display:none;">
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
                    <button onclick="showPage('login')">Log out</button>
                </li>
            </ul>
        </nav>
        <br>

        <h1>DASHBOARD</h1>

        <div class="cards">
            <div class="card">
                <h2>5</h2>
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
    </div>
</div>

<!-- USER HOME -->
<div id="page-USERHOME" class="page-section" style="display:none;">
    <div id="userhome">

        <nav class="navbar">
            <div class="logo">LearnLoop</div>

            <ul class="nav-links">
                <li class="list nav-USERHOME" onclick="showPage('USERHOME')"><a href="#">Browse</a></li>
                <li class="list nav-MATCHES" onclick="showPage('MATCHES')"><a href="#">Matches</a></li>
                <li class="list nav-EXCHANGES" onclick="showPage('EXCHANGES')"><a href="#">Exchanges</a></li>
                <li class="list nav-MESSAGES" onclick="showPage('MESSAGES')"><a href="#">Messages</a></li>
                <li class="list nav-PROFILE" onclick="showPage('PROFILE')"><a href="#">Profile</a></li>
                <li>
                    <button onclick="showPage('login')">Logout</button>
                </li>
            </ul>
        </nav>

        <div class="feed">
            <div class="post">
                <div class="post-header">
                    <img src="https://via.placeholder.com/50" alt="Profile">
                    <div>
                        <h3>Priya N.</h3>
                        <small>2 hours ago</small>
                    </div>
                </div>

                <p><strong>Offers:</strong> Conversational Spanish</p>
                <p><strong>Wants:</strong> Intro to Python</p>

                <p>
                    Weekly 30-minute chats. I'll help you improve
                    your Spanish while you teach me Python.
                </p>

                <div class="post-actions">
                    <span>👍 12 Likes</span>
                    <span>💬 4 Comments</span>
                </div>

                <button class="exchange-btn">Propose Exchange</button>
            </div>
        </div>
    </div>
</div>

<!-- MATCHES -->
<div id="page-MATCHES" class="page-section" style="display:none;">
    <div id="userhome">

        <nav class="navbar">
            <div class="logo">LearnLoop</div>

            <ul class="nav-links">
                <li class="list nav-USERHOME" onclick="showPage('USERHOME')">Browse</li>
                <li class="list nav-MATCHES" onclick="showPage('MATCHES')">Matches</li>
                <li class="list nav-EXCHANGES" onclick="showPage('EXCHANGES')">Exchanges</li>
                <li class="list nav-MESSAGES" onclick="showPage('MESSAGES')">Messages</li>
                <li class="list nav-PROFILE" onclick="showPage('PROFILE')">Profile</li>
                <li>
                    <button onclick="showPage('login')">Logout</button>
                </li>
            </ul>
        </nav>

        <h1>🤝 My Matches</h1>

        <div class="match-card">
            <img src="https://via.placeholder.com/70" alt="">
            <div>
                <h3>John Doe</h3>
                <p>React ↔ Photoshop</p>
                <small>Matched 2 days ago</small>
            </div>
            <button>Message</button>
        </div>

        <div class="match-card">
            <img src="https://via.placeholder.com/70" alt="">
            <div>
                <h3>Sarah Lee</h3>
                <p>English ↔ Java</p>
                <small>Matched Yesterday</small>
            </div>
            <button>Message</button>
        </div>
    </div>
</div>

<!-- EXCHANGES -->
<div id="page-EXCHANGES" class="page-section" style="display:none;">
    <div id="userhome">

        <nav class="navbar">
            <div class="logo">LearnLoop</div>

            <ul class="nav-links">
                <li class="list nav-USERHOME" onclick="showPage('USERHOME')">Browse</li>
                <li class="list nav-MATCHES" onclick="showPage('MATCHES')">Matches</li>
                <li class="list nav-EXCHANGES" onclick="showPage('EXCHANGES')">Exchanges</li>
                <li class="list nav-MESSAGES" onclick="showPage('MESSAGES')">Messages</li>
                <li class="list nav-PROFILE" onclick="showPage('PROFILE')">Profile</li>
                <li>
                    <button onclick="showPage('login')">Logout</button>
                </li>
            </ul>
        </nav>

        <h1>📚 Exchange Requests</h1>

        <div class="exchange-card">
            <h3>John Doe</h3>
            <p>Wants to learn: React</p>
            <p>Offers: Photoshop</p>
            <button>Accept</button>
            <button>Decline</button>
        </div>

        <div class="exchange-card">
            <h3>Maria Cruz</h3>
            <p>Wants to learn: English</p>
            <p>Offers: Graphic Design</p>
            <button>Accept</button>
            <button>Decline</button>
        </div>
    </div>
</div>

<!-- MESSAGES -->
<div id="page-MESSAGES" class="page-section" style="display:none;">
    <div id="userhome">

        <nav class="navbar">
            <div class="logo">LearnLoop</div>

            <ul class="nav-links">
                <li class="list nav-USERHOME" onclick="showPage('USERHOME')">Browse</li>
                <li class="list nav-MATCHES" onclick="showPage('MATCHES')">Matches</li>
                <li class="list nav-EXCHANGES" onclick="showPage('EXCHANGES')">Exchanges</li>
                <li class="list nav-MESSAGES" onclick="showPage('MESSAGES')">Messages</li>
                <li class="list nav-PROFILE" onclick="showPage('PROFILE')">Profile</li>
                <li>
                    <button onclick="showPage('login')">Logout</button>
                </li>
            </ul>
        </nav>

        <h1>💬 Messages</h1>

        <div class="message-card">
            <img src="https://via.placeholder.com/60" alt="">
            <div>
                <h3>John Doe</h3>
                <p>See you tomorrow at 7 PM!</p>
            </div>
        </div>

        <div class="message-card">
            <img src="https://via.placeholder.com/60" alt="">
            <div>
                <h3>Sarah Lee</h3>
                <p>Thanks for today's lesson.</p>
            </div>
        </div>
    </div>
</div>

<!-- PROFILE -->
<div id="page-PROFILE" class="page-section" style="display:none;">
    <div id="userhome">

        <nav class="navbar">
            <div class="logo">LearnLoop</div>

            <ul class="nav-links">
                <li class="list nav-USERHOME" onclick="showPage('USERHOME')">Browse</li>
                <li class="list nav-MATCHES" onclick="showPage('MATCHES')">Matches</li>
                <li class="list nav-EXCHANGES" onclick="showPage('EXCHANGES')">Exchanges</li>
                <li class="list nav-MESSAGES" onclick="showPage('MESSAGES')">Messages</li>
                <li class="list nav-PROFILE" onclick="showPage('PROFILE')">Profile</li>
                <li>
                    <button onclick="showPage('login')">Logout</button>
                </li>
            </ul>
        </nav>

        <h1>👤 My Profile</h1>

        <div class="profile-card">
            <img src="https://via.placeholder.com/120" alt="">
            <h2>Harold Caño</h2>
            <p>⭐ 4.9 Rating</p>
            <p><strong>Can Teach:</strong> Graphic Design</p>
            <p><strong>Want to Learn:</strong> React JS</p>
            <button>Edit Profile</button>
        </div>
    </div>
</div>

<script>
    // Equivalent of React's setPage(...) state — hides every section,
    // shows only the one matching the requested page id.
    function showPage(page) {
        document.querySelectorAll('.page-section').forEach(function (section) {
            section.style.display = 'none';
        });
        document.getElementById('page-' + page).style.display = 'block';

        // Equivalent of the `active` className toggling on the nav <li> items
        document.querySelectorAll('.nav-USERHOME, .nav-MATCHES, .nav-EXCHANGES, .nav-MESSAGES, .nav-PROFILE')
            .forEach(function (el) { el.classList.remove('active'); });
        document.querySelectorAll('.nav-' + page)
            .forEach(function (el) { el.classList.add('active'); });
    }

    // Equivalent of handleLogin() in App.jsx
    function handleLogin() {
        var username = document.getElementById('login-username').value;
        var password = document.getElementById('login-password').value;

        if (username === "admin" && password === "1234") {
            showPage('ADMINHOME');
        } else if (username === "user" && password === "1234") {
            showPage('USERHOME');
        } else {
            alert("Invalid username or password.");
        }
    }
</script>

</body>
</html>
--}}