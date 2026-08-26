<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LearnLoop - Messages</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div id="userhome">
    @include('partials.user-nav')

    <main class="main-content">
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
    </main>
</div>

</body>
</html>