<?php

require_once 'vendor/tpl.php';
require_once 'functions.php';
require_once 'DAO/Repository.php';
$repo = new Repository();

$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : "book-list";

if ($cmd === 'book-list') {
    $data = [
        'template' => 'bookList.html',
        'cmd' => $cmd,
        'firstColTitle' => 'Pealkiri',
        'secondColTitle' => 'Autorid',
        'thirdColTitle' => 'Hinne',
        'message' => isset($_GET['message']) ? $_GET['message'] === 'deleted' ? "Raamat kustutatud!" : ($_GET['message'] === 'updated' ? "Raamat uuendatud" : ($_GET['message'] === 'added' ? 'Raamat lisatud!' : '')) : '',
        'books' => $repo->getAllBooks()
    ];
    print renderTemplate('templates/main.html', $data);

} else if ($cmd === 'book-edit') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $data = [
        'template' => 'bookForm.html',
        'cmd' => $cmd,
        'book' => $repo->fetchBookById($id),
        'authors' => $repo->getAllAuthors()
    ];
    print renderTemplate('templates/main.html', $data);

} else if ($cmd === 'book-form') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $data = [
        'template' => 'bookForm.html',
        'cmd' => $cmd,
        'authors' => $repo->getAllAuthors()
    ];
    print renderTemplate('templates/main.html', $data);

} else if ($cmd === 'book-form-submit') {
    $id = intval($_POST['id']);
    $title = isset($_POST['title']) ? $_POST['title'] : "";
    $author1Id = isset($_POST['author1']) ? intval($_POST['author1']) : 0;
    $author2Id = isset($_POST['author2']) ? intval($_POST['author2']) : 0;
    $grade = isset($_POST['grade']) ? intval($_POST['grade']) : 0;
    $isRead = isset($_POST['isRead']) ? $_POST['isRead'] : false;
    $action = isset($_POST['submitButton']) ? $_POST['submitButton'] : $_POST['deleteButton'];
    $book = new Book($id, $title, $grade, $isRead);
    $book->addAuthor($repo->fetchAuthorById($author1Id), 0);
    $book->addAuthor($repo->fetchAuthorById($author2Id), 1);
    $errors = validateBook($book);
    if (isset($_POST['deleteButton'])) {
        $repo->deleteBook($book);
        header("Location: index.php?message=deleted");
    } else if (empty($errors)) {
        if (empty($repo->fetchBookById($book->id))) {
            $repo->addBook($book);
            header("Location: index.php?message=added");
        } else {
            $repo->editBook($book);
            header("Location: index.php?message=updated");
        }
    } else {
        $data = [
            'template' => 'bookForm.html',
            'cmd' => $cmd,
            'book' => $book,
            'errors' => $errors,
            'authors' => $repo->getAllAuthors()
        ];
        print renderTemplate('templates/main.html', $data);
    }
} else if ($cmd === 'author-list') {
    $data = [
        'template' => 'authorList.html',
        'cmd' => $cmd,
        'firstColTitle' => 'Eesnimi',
        'secondColTitle' => 'Perekonnanimi',
        'thirdColTitle' => 'Hinne',
        'message' => isset($_GET['message']) ? $_GET['message'] === 'deleted' ? "Autor kustutatud!" : ($_GET['message'] === 'updated' ? "Autor uuendatud" : ($_GET['message'] === 'added' ? 'Autor lisatud!' : '')) : '',
        'authors' => $repo->getAllAuthors()
    ];
    print renderTemplate('templates/main.html', $data);

} else if ($cmd === 'author-form') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $data = [
        'template' => 'authorForm.html',
        'cmd' => $cmd
    ];
    print renderTemplate('templates/main.html', $data);

} else if ($cmd === 'author-edit') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $data = [
        'template' => 'authorForm.html',
        'cmd' => $cmd,
        'author' => $repo->fetchAuthorById($id)
    ];
    print renderTemplate('templates/main.html', $data);

} else if ($cmd === 'author-form-submit') {
    $id = intval($_POST['id']);
    $firstName = isset($_POST['firstName']) ? $_POST['firstName'] : "";
    $lastName = isset($_POST['lastName']) ? $_POST['lastName'] : "";
    $grade = isset($_POST['grade']) ? intval($_POST['grade']) : 0;
    $action = isset($_POST['submitButton']) ? $_POST['submitButton'] : $_POST['deleteButton'];
    $author = new Author($id, $firstName, $lastName, $grade);
    $errors = validateAuthor($author);
    if (isset($_POST['deleteButton'])) {
        $repo->deleteAuthor($author);
        header("Location: index.php?cmd=author-list&message=deleted");
    } else if (empty($errors)) {
        if (empty($repo->fetchAuthorById($id))) {
            $repo->addAuthor($author);
            header("Location: index.php?cmd=author-list&message=added");
        } else {
            $repo->editAuthor($author);
            header("Location: index.php?cmd=author-list&message=updated");

        }
    } else {
        $data = [
            'template' => 'authorForm.html',
            'cmd' => $cmd,
            'author' => $author,
            'errors' => $errors
        ];
        print renderTemplate('templates/main.html', $data);
    }
}