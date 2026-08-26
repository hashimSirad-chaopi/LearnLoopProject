<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LearnLoop - Matches</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div id="userhome">
    @include('partials.user-nav')

    <main class="main-content">
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
    </main>
</div>

</body>
</html>