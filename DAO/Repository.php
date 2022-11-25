<?php
require_once "DTO/Book.php";
require_once "DTO/Author.php";
class Repository {
    public string $username = 'root';
    public string $password = 'fe57';
    public string $address = 'mysql:host=db;dbname=app';
    public PDO $connection;

    public function __construct() {
        $this->connection = new PDO($this->address, $this->username, $this->password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    public function addBook($book) {
        $stmt = $this->connection->prepare('INSERT INTO BOOK(book_id, title, rating, is_read) values (:id, :title, :grade, :is_read)');
        $stmt -> bindValue(':id', 0);
        $stmt->bindValue(":title", $book->title);
        $stmt->bindValue(":grade", $book->grade !== "" ? $book->grade : 0);
        $stmt->bindValue(":is_read", $book->isRead ? 1 : 0);
        $stmt->execute();

        //Get the lastly added book's ID
        $bookId = 0;
        $stmt = $this->connection->prepare('SELECT LAST_INSERT_ID()');
        $stmt->execute();
        foreach ($stmt as $item) {
            $bookId = $item['LAST_INSERT_ID()'];
        }
        if (isset($book->authors[0])) {
            $stmt = $this->connection->prepare('INSERT INTO BOOK_AUTHOR(book_author_id, book_id, author_id, author_position)
                                                        values (:id, :bookId, :authorId, :authorPos)');
            $stmt->bindValue(":id", 0);
            $stmt->bindValue(":bookId", $bookId);
            $stmt->bindValue(":authorId", $book->authors[0]->id);
            $stmt->bindValue(":authorPos", 0);
            $stmt->execute();
        }
        // Add the second book & author to BOOK_AUTHOR table
        if (isset($book->authors[1])) {
            $stmt = $this->connection->prepare('INSERT INTO BOOK_AUTHOR(book_author_id, book_id, author_id, author_position)
                                                        values (:id, :bookId, :authorId, :authorPos)');
            $stmt->bindValue(":id", 0);
            $stmt->bindValue(":bookId", $bookId);
            $stmt->bindValue(":authorId", $book->authors[1]->id);
            $stmt->bindValue(":authorPos", 1);
            $stmt->execute();
        }
    }

    public function deleteBook($book) {
        $stmt = $this->connection->prepare('DELETE FROM BOOK WHERE book_id = :id');
        $stmt->bindValue(":id", $book->id);
        $stmt->execute();
        $stmt = $this->connection->prepare('DELETE FROM BOOK_AUTHOR WHERE book_id = :id');
        $stmt->bindValue(":id", $book->id);
        $stmt->execute();
    }

    public function deleteAuthor($author) {
        $stmt = $this->connection->prepare('DELETE FROM AUTHOR WHERE author_id = :id');
        $stmt->bindValue(":id", $author->id);
        $stmt->execute();
    }

    public function addAuthor($author) {
        $stmt = $this->connection->prepare('INSERT INTO AUTHOR(author_id, first_name, last_name, grade)
    values (:id, :firstname, :lastname, :grade)');
        $stmt -> bindValue(':id', 0);
        $stmt->bindValue(":firstname", $author->firstName);
        $stmt->bindValue(":lastname", $author->lastName);
        $stmt->bindValue(":grade", $author->grade);
        $stmt->execute();
    }

    public function editAuthor($author) {
        $stmt = $this->connection->prepare('UPDATE AUTHOR SET first_name = :firstname, last_name = :lastname, grade = :grade
                                            WHERE author_id = :id');
        $stmt->bindValue(":firstname", $author->firstName);
        $stmt->bindValue(":lastname", $author->lastName);
        $stmt->bindValue(":grade", $author->grade);
        $stmt->bindValue(":id", $author->id);
        $stmt->execute();
    }

    public function getAllBooks()
    {
        $books = [];
        $stmt = $this->connection->prepare('select B.book_id, B.title, B.rating, B.is_read, BA.author_position, A.author_id, A.first_name, A.last_name, A.grade
                                                        from BOOK B
                                                        LEFT JOIN BOOK_AUTHOR BA on (B.book_id = BA.book_id)
                                                        LEFT JOIN AUTHOR A on (BA.author_id = A.author_id)
                                                        ORDER BY 5;');
        $stmt->execute();
        if (!empty($stmt)) {
            foreach ($stmt as $line) {
                $id = intval($line['book_id']);
                $title = $line['title'];
                $grade = intval($line['rating']);
                $isRead = $line['is_read'] === '1';
                $book = new Book($id, $title, $grade, $isRead);

                $authorId = intval($line['author_id']);
                $firstName = $line['first_name'];
                $lastName = $line['last_name'];
                $authorGrade = intval($line['grade']);
                $authorPos = intval($line['author_position']);
                if ($authorId !== 0) {
                    $inBooksList = 0;
                    $isInList = false;
                    foreach ($books as $bookInList) {
                        if ($bookInList->id === $book->id) {
                            $isInList = true;
                            break;
                        } else {
                            $inBooksList += 1;
                        }
                    }
                    if (!$isInList) {
                        $book->addAuthor(new Author($authorId, $firstName, $lastName, $authorGrade), $authorPos);
                        $books[] = $book;
                    } else {
                        $books[$inBooksList]->addAuthor(new Author($authorId, $firstName, $lastName, $authorGrade), $authorPos);
                    }
                } else {
                    $books[] = $book;
                    continue;
                }
            }
        }
        return $books;
    }

    public function fetchBookById($id) {
        $stmt = $this->connection->prepare('SELECT book_id, title, rating, is_read FROM BOOK WHERE book_id = :id');
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        foreach ($stmt as $item) {
            $id = intval($item['book_id']);
            $authors = $this->getBookAuthors($id);
            $title = $item['title'];
            $grade = intval($item['rating']);
            $isRead = $item['is_read'] === '1';
            $book = new Book($id, $title, $grade, $isRead);
            $book->setAuthors($authors);
            return $book;
        }
        return null;
    }

    public function fetchAuthorById($id) {
        $stmt = $this->connection->prepare('SELECT author_id, first_name, last_name, grade FROM AUTHOR WHERE author_id = :id');
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        foreach ($stmt as $item) {
            $id = intval($item['author_id']);
            $firstName = $item['first_name'];
            $lastName = $item['last_name'];
            $grade = intval($item['grade']);
            return new Author($id, $firstName, $lastName, $grade);
        }
        return null;
    }

    private function getBookAuthors($bookId) {
        $authors = [];

        $stmt = $this->connection->prepare('select book_id, BOOK_AUTHOR.author_id as authorId, BOOK_AUTHOR.author_position, first_name, last_name, grade, AUTHOR.author_id from BOOK_AUTHOR left join AUTHOR
                                                                             on book_id = :bookId  and AUTHOR.author_id = BOOK_AUTHOR.author_id');
        $stmt->bindValue(":bookId", $bookId);
        $stmt->execute();
        foreach ($stmt as $item) {
            if (intval($item['book_id']) === $bookId) {
                $id = $item['authorId'];
                $firstName = $item['first_name'];
                $lastName = $item['last_name'];
                $grade = $item['grade'];
                $authorPos = intval($item['author_position']);
                $author = new Author($id, $firstName, $lastName, $grade);
                $authors[$authorPos] = $author;
            }
        }
        return $authors;
    }

    public function getAllAuthors() {
        $authors = [];
        $stmt = $this->connection->prepare('select author_id, first_name, last_name, grade from AUTHOR');
        $stmt->execute();
        foreach ($stmt as $item) {
            $id = intval($item['author_id']);
            $firstName = $item['first_name'];
            $lastName = $item['last_name'];
            $grade = intval($item['grade']);
            $author = new Author($id, $firstName, $lastName, $grade);
            $authors[] = $author;
        }
        return $authors;
    }

    public function editBook($book) {

        $oldAuthors = $this->getBookAuthors($book->id);
        $stmt = $this->connection->prepare('UPDATE BOOK SET title = :title, rating = :grade, is_read = :isread
                                            WHERE book_id = :id');
        $stmt->bindValue(":title", $book->title);
        $stmt->bindValue(":grade", $book->grade !== "" ? $book->grade : 0);
        $stmt->bindValue(":isread", $book->isRead ? 1 : 0);
        $stmt->bindValue(":id", $book->id);
        $stmt->execute();

        if (isset($oldAuthors[1]) && isset($book->authors[1])) {
            $stmt = $this->connection->prepare('UPDATE BOOK_AUTHOR SET author_id = :authorId
                                        WHERE book_id = :id and author_id = :oldAuthorId');
            $stmt->bindValue(":id", $book->id);
            $stmt->bindValue(":authorId", $book->authors[1]->id);
            $stmt->bindValue(":oldAuthorId", $oldAuthors[1]->id);
            $stmt->execute();
        } else if (isset($oldAuthors[1]) && !isset($book->authors[1])) {
            $stmt = $this->connection->prepare('delete from BOOK_AUTHOR WHERE book_id = :id and author_id = :oldAuthorId');
            $stmt->bindValue(":id", $book->id);
            $stmt->bindValue(":oldAuthorId", $oldAuthors[1]->id);
            $stmt->execute();
        } else if (!isset($oldAuthors[1]) && isset($book->authors[1])) {
            $stmt = $this->connection->prepare('INSERT INTO BOOK_AUTHOR(book_author_id, book_id, author_id, author_position)
                                                        values (:id, :bookId, :authorId, :authorPos)');
            $stmt->bindValue(":id", 0);
            $stmt->bindValue(":bookId", $book->id);
            $stmt->bindValue(":authorId", $book->authors[1]->id);
            $stmt->bindValue(":authorPos", 1);
            $stmt->execute();
        }

        if (isset($oldAuthors[0]) && isset($book->authors[0])) {
            $stmt = $this->connection->prepare('UPDATE BOOK_AUTHOR SET author_id = :authorId
                                        WHERE book_id = :id and author_id = :oldAuthorId');
            $stmt->bindValue(":id", $book->id);
            $stmt->bindValue(":authorId", $book->authors[0]->id);
            $stmt->bindValue(":oldAuthorId", $oldAuthors[0]->id);
            $stmt->execute();
        } else if (isset($oldAuthors[0]) && !isset($book->authors[0])) {
            $stmt = $this->connection->prepare('delete from BOOK_AUTHOR WHERE book_id = :id and author_id = :oldAuthorId');
            $stmt->bindValue(":id", $book->id);
            $stmt->bindValue(":oldAuthorId", $oldAuthors[0]->id);
            $stmt->execute();
        } else if (!isset($oldAuthors[0]) && isset($book->authors[0])) {
            $stmt = $this->connection->prepare('INSERT INTO BOOK_AUTHOR(book_author_id, book_id, author_id, author_position)
                                                        values (:id, :bookId, :authorId, :authorPos)');
            $stmt->bindValue(":id", 0);
            $stmt->bindValue(":bookId", $book->id);
            $stmt->bindValue(":authorId", $book->authors[0]->id);
            $stmt->bindValue(":authorPos", 0);
            $stmt->execute();
        }
    }
}