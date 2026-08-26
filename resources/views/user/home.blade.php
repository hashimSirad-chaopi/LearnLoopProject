<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LearnLoop - Browse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div id="userhome">
    @include('partials.user-nav')

    <main class="main-content">
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
                <p>Weekly 30-minute chats. I'll help you improve your Spanish while you teach me Python.</p>

                <div class="post-actions">
                    <span>👍 12 Likes</span>
                    <span>💬 4 Comments</span>
                </div>

                <button class="exchange-btn">Propose Exchange</button>
            </div>
        </div>
    </main>
</div>

</body>
</html>