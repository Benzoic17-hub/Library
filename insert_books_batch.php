<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "library";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$books = [
    ['Harry Potter and the Chamber of Secrets', 'J.K. Rowling', 'Fantasy', '9780439064873', 9],
    ['Harry Potter and the Prisoner of Azkaban', 'J.K. Rowling', 'Fantasy', '9780439136365', 8],
    ['Harry Potter and the Goblet of Fire', 'J.K. Rowling', 'Fantasy', '9780439139601', 10],
    ['Harry Potter and the Order of the Phoenix', 'J.K. Rowling', 'Fantasy', '9780439358071', 7],
    ['Harry Potter and the Half-Blood Prince', 'J.K. Rowling', 'Fantasy', '9780439785969', 6],
    ['Harry Potter and the Deathly Hallows', 'J.K. Rowling', 'Fantasy', '9780545010221', 8],
    ['Percy Jackson and the Lightning Thief', 'Rick Riordan', 'Fantasy', '9780786838653', 7],
    ['Percy Jackson and the Sea of Monsters', 'Rick Riordan', 'Fantasy', '9781423103349', 6],
    ['Percy Jackson and the Titan’s Curse', 'Rick Riordan', 'Fantasy', '9781423101451', 5],
    ['Percy Jackson and the Battle of the Labyrinth', 'Rick Riordan', 'Fantasy', '9781423101499', 7],
    ['Percy Jackson and the Last Olympian', 'Rick Riordan', 'Fantasy', '9781423101475', 8],
    ['The Hunger Games', 'Suzanne Collins', 'Dystopian', '9780439023481', 9],
    ['Catching Fire', 'Suzanne Collins', 'Dystopian', '9780439023498', 8],
    ['Mockingjay', 'Suzanne Collins', 'Dystopian', '9780439023511', 7],
    ['Divergent', 'Veronica Roth', 'Dystopian', '9780062024039', 6],
    ['Insurgent', 'Veronica Roth', 'Dystopian', '9780062024046', 5],
    ['Allegiant', 'Veronica Roth', 'Dystopian', '9780062024060', 4],
    ['The Maze Runner', 'James Dashner', 'Dystopian', '9780385737950', 7],
    ['The Scorch Trials', 'James Dashner', 'Dystopian', '9780385738759', 6],
    ['The Death Cure', 'James Dashner', 'Dystopian', '9780385738773', 5],
    ['The Kill Order', 'James Dashner', 'Dystopian', '9780385742886', 4],
    ['The Fever Code', 'James Dashner', 'Dystopian', '9780553513097', 6],
    ['Twilight', 'Stephenie Meyer', 'Romance', '9780316015844', 7],
    ['New Moon', 'Stephenie Meyer', 'Romance', '9780316160193', 6],
    ['Eclipse', 'Stephenie Meyer', 'Romance', '9780316160209', 5],
    ['Breaking Dawn', 'Stephenie Meyer', 'Romance', '9780316067928', 7],
    ['The Fault in Our Stars', 'John Green', 'Romance', '9780525478812', 6],
    ['Paper Towns', 'John Green', 'Mystery', '9780525478188', 5],
    ['Looking for Alaska', 'John Green', 'Drama', '9780142402511', 4],
    ['An Abundance of Katherines', 'John Green', 'Romance', '9780142410707', 3],
    ['Turtles All the Way Down', 'John Green', 'Drama', '9780525555360', 4],
    ['The Perks of Being a Wallflower', 'Stephen Chbosky', 'Coming-of-Age', '9780671027346', 5],
    ['Artemis Fowl', 'Eoin Colfer', 'Fantasy', '9780786808014', 4],
    ['Artemis Fowl: The Arctic Incident', 'Eoin Colfer', 'Fantasy', '9780786816149', 3],
    ['Artemis Fowl: The Eternity Code', 'Eoin Colfer', 'Fantasy', '9780786819140', 4],
    ['Artemis Fowl: The Opal Deception', 'Eoin Colfer', 'Fantasy', '9780786852895', 3],
    ['Eragon', 'Christopher Paolini', 'Fantasy', '9780375826696', 6],
    ['Eldest', 'Christopher Paolini', 'Fantasy', '9780375826702', 5],
    ['Brisingr', 'Christopher Paolini', 'Fantasy', '9780375826726', 4],
    ['Inheritance', 'Christopher Paolini', 'Fantasy', '9780375826733', 6],
    ['The Golden Compass', 'Philip Pullman', 'Fantasy', '9780440418320', 5],
    ['The Subtle Knife', 'Philip Pullman', 'Fantasy', '9780440418337', 4],
    ['The Amber Spyglass', 'Philip Pullman', 'Fantasy', '9780440418566', 3],
    ['The Lion, the Witch and the Wardrobe', 'C.S. Lewis', 'Fantasy', '9780064471046', 7],
    ['Prince Caspian', 'C.S. Lewis', 'Fantasy', '9780064471053', 6],
    ['The Voyage of the Dawn Treader', 'C.S. Lewis', 'Fantasy', '9780064471077', 6],
    ['The Silver Chair', 'C.S. Lewis', 'Fantasy', '9780064471091', 5],
    ['The Horse and His Boy', 'C.S. Lewis', 'Fantasy', '9780064471060', 4],
    ['The Magician’s Nephew', 'C.S. Lewis', 'Fantasy', '9780064471107', 5],
    ['The Last Battle', 'C.S. Lewis', 'Fantasy', '9780064471084', 6]
];

foreach ($books as $book) {
    [$title, $author, $category, $isbn, $copies] = $book;
    $sql = "INSERT INTO books (title, author, category, isbn, copies_available)
            VALUES ('$title', '$author', '$category', '$isbn', $copies)";
    if (!$conn->query($sql)) {
        echo "❌ Error inserting '$title': " . $conn->error . "<br>";
    }
}

echo "<p style='color:green;'>✅ All 50 books inserted successfully!</p>";
$conn->close();
?>
