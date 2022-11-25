CREATE TABLE BOOK(
    book_id int,
    title varchar(255),
    grade int,
    is_read boolean,
    rating int,
    PRIMARY KEY (book_id)
);

CREATE TABLE AUTHOR(
    author_id int,
    first_name varchar(255),
    last_name varchar(255),
    grade int,
    PRIMARY KEY (author_id)
);

CREATE TABLE BOOK_AUTHOR(
    book_author_id int,
    book_id int,
    author_id int,
    author_position int,
    FOREIGN KEY (book_id) REFERENCES BOOK(book_id),
    FOREIGN KEY (author_id) REFERENCES AUTHOR(author_id)
);