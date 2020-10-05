-- Table: public.bookshelf

-- DROP TABLE public.bookshelf;

CREATE TABLE public.bookshelf
(
    id integer NOT NULL,
    title text COLLATE pg_catalog."default",
    isbn character varying(13) COLLATE pg_catalog."default",
    author text COLLATE pg_catalog."default",
    publisher text COLLATE pg_catalog."default",
    publishe_date date,
    entry_date date,
    description text COLLATE pg_catalog."default",
    thumbnail_url text COLLATE pg_catalog."default",
    CONSTRAINT bookshelf_pkey PRIMARY KEY (id)
)

TABLESPACE pg_default;

ALTER TABLE public.bookshelf
    OWNER to postgres;