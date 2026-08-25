<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LearnLoop - Exchanges</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div id="userhome">
    @include('partials.user-nav')

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

</body>
</html>