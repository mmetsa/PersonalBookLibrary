<?php
require_once "DAO/Repository.php";

function validateBook($book) {
    $errors = [];
    if (strlen($book->title) < 3 || strlen($book->title) > 23) {
        $errors[] = "Raamatu pealkirja pikkus peab olema 3 - 23 tähemärki";
    }
    if (isset($book->authors[0]) && isset($book->authors[1]) && $book->authors[0]->id === $book->authors[1]->id) {
        $errors[] = "Esimene ja Teine autor ei saa olla samad!";
    }

    return $errors;
}

function validateAuthor($author) {
    $errors = [];
    if (strlen($author->firstName) < 1 || strlen($author->firstName) > 21) {
        $errors[] = "Autori eesnime pikkus peab olema 1 - 21 tähemärki";
    }
    if (strlen($author->lastName) < 2 || strlen($author->lastName) > 22) {
        $errors[] = "Autori perekonnanime pikkus peab olema 2 - 22 tähemärki";
    }

    return $errors;
}