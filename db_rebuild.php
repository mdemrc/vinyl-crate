<?php
$host = "127.0.0.1";
$user = "root";
$passwords = ["123456", ""];
$conn = null;
$selectedPassword = "";

foreach ($passwords as $pwd) {
    $conn = @new mysqli($host, $user, $pwd);
    if (!$conn->connect_error) {
        $selectedPassword = $pwd;
        break;
    }
}

if ($conn->connect_error) {
    die("Database connection failed for all attempts: " . $conn->connect_error);
}

$conn->query("DROP DATABASE IF EXISTS vinyl_crate");
if (!$conn->query("CREATE DATABASE vinyl_crate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die("Failed to create database: " . $conn->error);
}
$conn->select_db("vinyl_crate");
$conn->set_charset("utf8mb4");

$tables = [
    "users" => "CREATE TABLE users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "genres" => "CREATE TABLE genres (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(60) NOT NULL UNIQUE,
        slug VARCHAR(60) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "albums" => "CREATE TABLE albums (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        artist VARCHAR(120) NOT NULL,
        genre_id INT UNSIGNED DEFAULT NULL,
        release_year SMALLINT UNSIGNED NOT NULL,
        record_label VARCHAR(100) DEFAULT NULL,
        cover VARCHAR(255) DEFAULT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_albums_genre FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB",

    "tracks" => "CREATE TABLE tracks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        album_id INT UNSIGNED NOT NULL,
        position INT UNSIGNED NOT NULL,
        title VARCHAR(150) NOT NULL,
        duration_seconds INT UNSIGNED DEFAULT NULL,
        CONSTRAINT fk_tracks_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB",

    "reviews" => "CREATE TABLE reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        album_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_review (album_id, user_id),
        CONSTRAINT fk_reviews_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB",

    "crate_items" => "CREATE TABLE crate_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        album_id INT UNSIGNED NOT NULL,
        status ENUM('owned', 'wishlist') NOT NULL DEFAULT 'owned',
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_crate (user_id, album_id),
        CONSTRAINT fk_crate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_crate_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB",

    "review_likes" => "CREATE TABLE review_likes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        review_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_review_like (review_id, user_id),
        CONSTRAINT fk_rl_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_rl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB",
];

foreach ($tables as $name => $sql) {
    if (!$conn->query($sql)) {
        die("Failed to create table $name: " . $conn->error);
    }
}

// Users: one administrator and one regular demo account
$users = [
    ["admin", "admin123", "admin@vinylcrate.test", 1],
    ["demo", "demo123", "demo@vinylcrate.test", 0],
    ["alex", "alex123", "alex@vinylcrate.test", 0],
    ["sam", "sam123", "sam@vinylcrate.test", 0],
    ["nina", "nina123", "nina@vinylcrate.test", 0],
];
$userStmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (?, ?, ?, ?)");
foreach ($users as $u) {
    $hash = password_hash($u[1], PASSWORD_DEFAULT);
    $userStmt->bind_param("sssi", $u[0], $hash, $u[2], $u[3]);
    $userStmt->execute();
}
$userStmt->close();

// Genres
$genres = ["Rock", "Jazz", "Pop", "Hip-Hop", "Electronic", "Soul", "Funk", "Classical"];
$genreStmt = $conn->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");
foreach ($genres as $g) {
    $slug = strtolower(str_replace(" ", "-", $g));
    $genreStmt->bind_param("ss", $g, $slug);
    $genreStmt->execute();
}
$genreStmt->close();

// Albums: [title, artist, genre_id, year, label, cover, description]
$albums = [
    ["The Dark Side of the Moon", "Pink Floyd", 1, 1973, "Harvest",
        "covers/seed/dark-side-of-the-moon.jpg",
        "Pink Floyd's 1973 landmark: one continuous suite on time, money and madness, wrapped in Alan Parsons' pristine engineering."],
    ["Kind of Blue", "Miles Davis", 2, 1959, "Columbia",
        "covers/seed/kind-of-blue.jpg",
        "Miles Davis' 1959 modal masterpiece, cut in two sessions with an all-star sextet. The most influential jazz record ever made."],
    ["Thriller", "Michael Jackson", 3, 1982, "Epic",
        "covers/seed/thriller.jpg",
        "Michael Jackson and Quincy Jones' 1982 blockbuster, still the best-selling album of all time."],
    ["Rumours", "Fleetwood Mac", 1, 1977, "Warner Bros.",
        "covers/seed/rumours.jpg",
        "Fleetwood Mac turned personal turmoil into 1977's most enduring soft-rock record."],
    ["To Pimp a Butterfly", "Kendrick Lamar", 4, 2015, "Top Dawg / Aftermath",
        "covers/seed/to-pimp-a-butterfly.jpg",
        "Kendrick Lamar's sprawling 2015 statement, fusing jazz, funk and spoken word into a portrait of Black America."],
    ["Random Access Memories", "Daft Punk", 5, 2013, "Columbia",
        "covers/seed/random-access-memories.jpg",
        "Daft Punk's 2013 love letter to 70s and 80s studio craft, recorded with live session legends."],
    ["What's Going On", "Marvin Gaye", 6, 1971, "Tamla",
        "covers/seed/whats-going-on.jpg",
        "Marvin Gaye's 1971 concept album on war, poverty and ecology: soul music with a conscience."],
    ["Abbey Road", "The Beatles", 1, 1969, "Apple",
        "covers/seed/abbey-road.jpg",
        "The Beatles' 1969 farewell, famous for its seamless side-two medley and that zebra-crossing sleeve."],
    ["OK Computer", "Radiohead", 1, 1997, "Parlophone",
        "covers/seed/ok-computer.jpg",
        "Radiohead's 1997 leap into art-rock paranoia, widely hailed as one of the greatest albums of its decade."],
    ["Innervisions", "Stevie Wonder", 6, 1973, "Tamla",
        "covers/seed/innervisions.jpg",
        "Stevie Wonder's 1973 high point: socially aware funk and soul played almost entirely by himself."],
];
$albumStmt = $conn->prepare("INSERT INTO albums (title, artist, genre_id, release_year, record_label, cover, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($albums as $a) {
    $albumStmt->bind_param("ssiisss", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6]);
    $albumStmt->execute();
}
$albumStmt->close();

// Tracks: [album_id, position, title, duration_seconds]
$tracks = [
    [1, 1, "Speak to Me", 90], [1, 2, "Breathe (In the Air)", 163], [1, 3, "On the Run", 216],
    [1, 4, "Time", 421], [1, 5, "The Great Gig in the Sky", 276], [1, 6, "Money", 382],
    [1, 7, "Us and Them", 461], [1, 8, "Brain Damage", 226], [1, 9, "Eclipse", 123],

    [2, 1, "So What", 562], [2, 2, "Freddie Freeloader", 586], [2, 3, "Blue in Green", 337],
    [2, 4, "All Blues", 691], [2, 5, "Flamenco Sketches", 566],

    [3, 1, "Wanna Be Startin' Somethin'", 363], [3, 2, "Baby Be Mine", 260], [3, 3, "Thriller", 357],
    [3, 4, "Beat It", 258], [3, 5, "Billie Jean", 294], [3, 6, "Human Nature", 246],

    [4, 1, "Second Hand News", 177], [4, 2, "Dreams", 257], [4, 3, "Never Going Back Again", 134],
    [4, 4, "Don't Stop", 191], [4, 5, "Go Your Own Way", 218], [4, 6, "The Chain", 268],

    [5, 1, "Wesley's Theory", 287], [5, 2, "King Kunta", 234], [5, 3, "These Walls", 300],
    [5, 4, "Alright", 219], [5, 5, "Momma", 283], [5, 6, "The Blacker the Berry", 328],

    [6, 1, "Give Life Back to Music", 274], [6, 2, "Giorgio by Moroder", 544], [6, 3, "Within", 228],
    [6, 4, "Instant Crush", 337], [6, 5, "Lose Yourself to Dance", 353], [6, 6, "Get Lucky", 369],

    [7, 1, "What's Going On", 233], [7, 2, "What's Happening Brother", 145],
    [7, 3, "Save the Children", 254], [7, 4, "Mercy Mercy Me (The Ecology)", 195],
    [7, 5, "Inner City Blues (Make Me Wanna Holler)", 314],

    [8, 1, "Come Together", 259], [8, 2, "Something", 182], [8, 3, "Oh! Darling", 207],
    [8, 4, "Octopus's Garden", 171], [8, 5, "Here Comes the Sun", 185], [8, 6, "Because", 165],

    [9, 1, "Airbag", 284], [9, 2, "Paranoid Android", 383], [9, 3, "Subterranean Homesick Alien", 267],
    [9, 4, "Exit Music (For a Film)", 264], [9, 5, "Karma Police", 261], [9, 6, "No Surprises", 229],

    [10, 1, "Too High", 276], [10, 2, "Visions", 314], [10, 3, "Living for the City", 441],
    [10, 4, "Golden Lady", 280], [10, 5, "Higher Ground", 222], [10, 6, "Don't You Worry 'bout a Thing", 264],
];
$trackStmt = $conn->prepare("INSERT INTO tracks (album_id, position, title, duration_seconds) VALUES (?, ?, ?, ?)");
foreach ($tracks as $t) {
    $trackStmt->bind_param("iisi", $t[0], $t[1], $t[2], $t[3]);
    $trackStmt->execute();
}
$trackStmt->close();

// Reviews: [album_id, user_id, rating, content] (order matters: review ids feed review_likes below)
$reviews = [
    [1, 1, 5, "A timeless masterpiece. The seamless flow from one track to the next still gives me chills on a good turntable."],
    [1, 2, 5, "My desert-island record. The original pressing sounds incredible on vinyl."],
    [2, 1, 5, "Modal jazz at its most elegant. 'So What' never gets old."],
    [3, 2, 4, "Pop perfection. Side A is flawless; side B slows down a touch but it is still essential."],
    [5, 1, 5, "Dense, jazzy and political. It rewards every repeat listen."],
    [6, 2, 4, "Lush analog production. 'Get Lucky' is the hit, but 'Giorgio' is the heart of the record."],
    [8, 2, 5, "The side-two medley is one of the greatest sequences ever committed to wax."],
    [9, 1, 4, "Paranoid and beautiful. It aged better than almost anything else from the nineties."],
    [1, 3, 5, "Headphones, lights off, side one through to 'Eclipse'. Nothing else sounds like it."],
    [1, 4, 4, "A bit overplayed, but the engineering still holds up better than records made fifty years later."],
    [3, 3, 5, "'Billie Jean' and 'Beat It' back to back is an unfair amount of hits on one side."],
    [3, 5, 4, "The production is so clean it almost sounds modern. A genuine pop landmark."],
    [8, 1, 5, "'Here Comes the Sun' on a clean pressing is pure sunlight. Desert-island side two."],
    [8, 3, 4, "The medley is the star, but 'Come Together' opens it perfectly."],
    [5, 4, 4, "Demanding and rewarding in equal measure. Not background music."],
    [5, 5, 5, "The jazz players elevate every track. 'Alright' became an anthem for a reason."],
    [2, 3, 4, "Quiet, patient, endlessly re-listenable. The benchmark for the genre."],
    [4, 2, 4, "Heartbreak turned into the catchiest songs of the seventies."],
    [4, 3, 5, "Every member was writing their best. 'The Chain' is flawless."],
    [9, 4, 5, "'Paranoid Android' is a whole journey on its own. Aged like fine wine."],
    [6, 5, 5, "Warm, analog and joyful. The session musicians make all the difference."],
    [10, 5, 4, "Stevie playing nearly everything himself, and it grooves the whole way through."],
    [7, 4, 5, "A concept album that still feels urgent. 'Mercy Mercy Me' is gorgeous."],
];
$reviewStmt = $conn->prepare("INSERT INTO reviews (album_id, user_id, rating, content) VALUES (?, ?, ?, ?)");
foreach ($reviews as $r) {
    $reviewStmt->bind_param("iiis", $r[0], $r[1], $r[2], $r[3]);
    $reviewStmt->execute();
}
$reviewStmt->close();

// Review likes: [review_id, user_id] (a member finds a review helpful; never their own)
$reviewLikes = [
    [1, 2], [1, 3], [1, 4], [1, 5],
    [2, 1], [2, 3],
    [5, 3], [5, 4], [5, 5],
    [7, 1], [7, 3], [7, 5],
    [9, 1], [9, 2],
    [11, 2], [11, 5],
    [13, 2], [13, 4],
    [16, 1], [16, 4],
    [4, 3],
    [19, 2],
];
$likeStmt = $conn->prepare("INSERT INTO review_likes (review_id, user_id) VALUES (?, ?)");
foreach ($reviewLikes as $l) {
    $likeStmt->bind_param("ii", $l[0], $l[1]);
    $likeStmt->execute();
}
$likeStmt->close();

// Crate items: [user_id, album_id, status]
$crate = [
    [2, 1, "owned"], [2, 2, "owned"], [2, 3, "owned"], [2, 8, "owned"],
    [2, 5, "wishlist"], [2, 6, "wishlist"],
    [1, 1, "owned"], [1, 9, "owned"], [1, 5, "wishlist"],
    [3, 1, "owned"], [3, 4, "owned"], [3, 9, "owned"], [3, 5, "wishlist"],
    [4, 5, "owned"], [4, 7, "owned"], [4, 1, "wishlist"], [4, 3, "wishlist"],
    [5, 3, "owned"], [5, 6, "owned"], [5, 10, "owned"], [5, 8, "wishlist"],
];
$crateStmt = $conn->prepare("INSERT INTO crate_items (user_id, album_id, status) VALUES (?, ?, ?)");
foreach ($crate as $c) {
    $crateStmt->bind_param("iis", $c[0], $c[1], $c[2]);
    $crateStmt->execute();
}
$crateStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database rebuilt</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<main class="container">
    <h1>Vinyl Crate database is ready</h1>
    <p class="message ok">Database <strong>vinyl_crate</strong> was recreated successfully.</p>
    <ul>
        <li>Tables: <code>users</code>, <code>genres</code>, <code>albums</code>, <code>tracks</code>, <code>reviews</code>, <code>crate_items</code>, <code>review_likes</code>.</li>
        <li>Seeded 8 genres, 10 albums with full tracklists, 5 users, reviews, helpful votes and crate entries.</li>
        <li>Administrator account: <strong>admin</strong> / <strong>admin123</strong>.</li>
        <li>Regular accounts: <strong>demo</strong> / <strong>demo123</strong> (also alex, sam, nina with the <em>name</em>123 pattern).</li>
        <li>Connected using the <strong><?php echo $selectedPassword === "" ? "empty" : "123456"; ?></strong> MySQL password.</li>
    </ul>
    <p><a href="index.php">Enter the application</a></p>
</main>
</body>
</html>
