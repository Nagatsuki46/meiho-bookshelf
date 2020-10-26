--
-- PostgreSQL database dump
--

-- Dumped from database version 12.4
-- Dumped by pg_dump version 12.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: bookshelf; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bookshelf (
    id integer NOT NULL,
    title text,
    isbn character varying(13),
    author text,
    publisher text,
    publishe_date text,
    entry_date date,
    description text,
    thumbnail_url text,
    checkout_flg smallint DEFAULT 0,
    checkout_date date,
    update_ts timestamp without time zone,
    employee_id text,
    exp_return_date date,
    return_date date,
    cover_image bytea,
    category_id smallint
);


ALTER TABLE public.bookshelf OWNER TO postgres;

--
-- Name: history; Type: TABLE; Schema: public; Owner: ka-fukai
--

CREATE TABLE public.history (
    id integer NOT NULL,
    return_ts timestamp without time zone NOT NULL,
    employee_id text,
    checkout_date date,
    exp_return_date date,
    return_date date,
    rate real,
    review text
);


ALTER TABLE public.history OWNER TO "ka-fukai";

--
-- Name: item; Type: TABLE; Schema: public; Owner: ka-fukai
--

CREATE TABLE public.item (
    id integer NOT NULL,
    name text NOT NULL,
    price integer,
    CONSTRAINT price_check_constraint CHECK ((price > 0))
);


ALTER TABLE public.item OWNER TO "ka-fukai";

--
-- Data for Name: bookshelf; Type: TABLE DATA; Schema: public; Owner: postgres
--