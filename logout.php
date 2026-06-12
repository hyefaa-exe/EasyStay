<?php
session_start();
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Logout | EasyStay</title>
    <meta name="description" content="EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary-orange: #C5A880;
            --hover-orange: #B3966F;
            --soft-white: #ffffff;
            --deep-black: #0a0a0a;
        }

        body.logout-page {
            background-color: var(--deep-black);
            background-image: radial-gradient(circle at center, #1a1a1a 0%, #0a0a0a 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }

        .logout-box {
            background: var(--soft-white);
            padding: 50px 40px;
            border-radius: 20px;
            border: 1.5px solid var(--primary-orange);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            max-width: 480px;
            width: 90%;
            text-align: center;
        }

        .logout-logo {
            height: 45px;
            margin-bottom: 25px;
        }

        .logout-box h2 {
            font-weight: 800;
            color: #111;
            font-size: 2rem;
            margin-bottom: 12px;
        }

        .logout-box p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .status-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 12px;
            background: #ffffff;
            border: 1px dashed #e0e0e0;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .status-row span {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .status-row i {
            color: var(--primary-orange);
            font-size: 18px;
        }

        .timer-box {
            background: #1a1a1a;
            color: #ffffff;
            padding: 12px 25px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 25px;
        }

        .timer-circle {
            background: var(--primary-orange);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* Kemaskini Butang Oren Minimalist */
        .btn-logout-primary {
            background: var(--primary-orange);
            color: #fff !important;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            display: block;
            width: 100%;
            border: none;
            transition: 0.3s ease;
            text-transform: capitalize;
        }

        .btn-logout-primary:hover {
            background: var(--hover-orange);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(197, 168, 128, 0.3);
        }

        .btn-back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }

        .btn-back-link:hover {
            color: var(--hover-orange);
            text-decoration: underline;
        }
    </style>
</head>

<body class="logout-page">

    <div class="logout-box">
        <img src="img/favicon.png?v=2" alt="Logo" class="logout-logo">

        <h2>Logged Out</h2>
        <p>You have been safely signed out. Thank you for visiting <strong>EasyStay</strong>.</p>

        <div class="status-row">
            <span>Session Ended Safely</span>
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <div class="timer-box">
            <span>Redirecting in</span>
            <div class="timer-circle" id="timer">3</div>
        </div>

        <a href="index.php" class="btn-logout-primary">
            Back to Home
        </a>
    </div>

    <script>
        let timeLeft = 3;
        const timerElem = document.getElementById('timer');
        const countdown = setInterval(() => {
            timeLeft--;
            timerElem.innerText = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.href = 'index.php';
            }
        }, 1000);
    </script>

</body>

</html>