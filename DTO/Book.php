<?php

class Book {

    public int $id;
    public string $title;
    public int $grade;
    public bool $isRead;
    public array $authors = [];

    public function __construct($id, $title, $grade, $isRead) {
        $this->id = $id;
        $this->title = $title;
        $this->grade = $grade;
        $this->isRead = $isRead;
    }

    public function addAuthor($author, $pos) {
        $this->authors[$pos] = $author;
    }

    public function setAuthors($authors) {
        $this->authors = $authors;
    }

}
