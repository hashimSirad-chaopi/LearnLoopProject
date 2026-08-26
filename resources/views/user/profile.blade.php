<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LearnLoop - Profile</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div id="userhome">
    @include('partials.user-nav')

    <main class="main-content">
        <h1>👤 My Profile</h1>

        <div class="profile-card">
            <img src="https://via.placeholder.com/120" alt="">
            <h2>{{ auth()->user()->name }}</h2>
            <p>⭐ 4.9 Rating</p>
            <p><strong>Can Teach:</strong> Graphic Design</p>
            <p><strong>Want to Learn:</strong> React JS</p>
            <button>Edit Profile</button>
        </div>
    </main>
</div>

</body>
</html>