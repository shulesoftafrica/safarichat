--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.1
-- Dumped by pg_dump version 14.2

-- Started on 2023-03-04 19:46:46

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

--
-- TOC entry 7 (class 2615 OID 1866790)
-- Name: dikodiko; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA dikodiko;


ALTER SCHEMA dikodiko OWNER TO postgres;

--
-- TOC entry 310 (class 1255 OID 1866791)
-- Name: count_message_parts(); Type: FUNCTION; Schema: dikodiko; Owner: postgres
--

CREATE FUNCTION dikodiko.count_message_parts() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

BEGIN 
if(length(NEW.body) >=0 and length(NEW.body) <=160) then
      UPDATE dikodiko.messages set sms_count=1 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >160 and char_length(NEW.body)<=306) then
 UPDATE dikodiko.messages set sms_count=2 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >306 and char_length(NEW.body)<=459) then
 UPDATE dikodiko.messages set sms_count=3 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >459 and char_length(NEW.body)<=612) then
 UPDATE dikodiko.messages set sms_count=4 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >=612 and char_length(NEW.body)<=765) then
UPDATE dikodiko.messages set sms_count=5 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >765 and char_length(NEW.body)<=918) then
UPDATE dikodiko.messages set sms_count=6 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >918 and char_length(NEW.body)<=1071) then
UPDATE dikodiko.messages set sms_count=7 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >1071 and char_length(NEW.body)<=1224) then
UPDATE dikodiko.messages set sms_count=8 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >1224 and char_length(NEW.body)<=1377) then
UPDATE dikodiko.messages set sms_count=9 where id=NEW.id;
	  RETURN NEW;
elseif(char_length(NEW.body) >1377 and char_length(NEW.body) <=1530) then
UPDATE dikodiko.messages set sms_count=10 where id=NEW.id;
	  RETURN NEW;
ELSE
UPDATE dikodiko.messages set sms_count=0 where id=NEW.id;
	  RETURN NEW;
END IF;
END ;

$$;


ALTER FUNCTION dikodiko.count_message_parts() OWNER TO postgres;

SET default_tablespace = '';

--
-- TOC entry 186 (class 1259 OID 1866792)
-- Name: admin_bookings; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_bookings (
    id integer NOT NULL,
    order_id character varying,
    amount numeric(10,2),
    token character varying,
    user_id integer,
    methods character varying,
    reference character varying,
    status smallint DEFAULT 1,
    gateway_buyer_uuid character varying,
    qr character varying,
    payment_gateway_url character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    admin_package_id integer,
    description text
);


ALTER TABLE dikodiko.admin_bookings OWNER TO postgres;

--
-- TOC entry 187 (class 1259 OID 1866800)
-- Name: admin_bookings_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_bookings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_bookings_id_seq OWNER TO postgres;

--
-- TOC entry 2855 (class 0 OID 0)
-- Dependencies: 187
-- Name: admin_bookings_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_bookings_id_seq OWNED BY dikodiko.admin_bookings.id;


--
-- TOC entry 188 (class 1259 OID 1866802)
-- Name: admin_feature_packages; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_feature_packages (
    id integer NOT NULL,
    admin_package_id integer,
    admin_feature_id integer,
    value character varying,
    description text,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.admin_feature_packages OWNER TO postgres;

--
-- TOC entry 189 (class 1259 OID 1866809)
-- Name: admin_feature_packages_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_feature_packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_feature_packages_id_seq OWNER TO postgres;

--
-- TOC entry 2856 (class 0 OID 0)
-- Dependencies: 189
-- Name: admin_feature_packages_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_feature_packages_id_seq OWNED BY dikodiko.admin_feature_packages.id;


--
-- TOC entry 190 (class 1259 OID 1866811)
-- Name: admin_features; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_features (
    id integer NOT NULL,
    name character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    code_name character varying
);


ALTER TABLE dikodiko.admin_features OWNER TO postgres;

--
-- TOC entry 191 (class 1259 OID 1866818)
-- Name: admin_features_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_features_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_features_id_seq OWNER TO postgres;

--
-- TOC entry 2857 (class 0 OID 0)
-- Dependencies: 191
-- Name: admin_features_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_features_id_seq OWNED BY dikodiko.admin_features.id;


--
-- TOC entry 192 (class 1259 OID 1866820)
-- Name: admin_integration_requests; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_integration_requests (
    id integer NOT NULL,
    user_id integer,
    is_paid integer DEFAULT 0,
    phone character varying,
    approved integer,
    approved_by character varying,
    approved_time timestamp without time zone,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.admin_integration_requests OWNER TO postgres;

--
-- TOC entry 193 (class 1259 OID 1866828)
-- Name: admin_integration_requests_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_integration_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_integration_requests_id_seq OWNER TO postgres;

--
-- TOC entry 2858 (class 0 OID 0)
-- Dependencies: 193
-- Name: admin_integration_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_integration_requests_id_seq OWNED BY dikodiko.admin_integration_requests.id;


--
-- TOC entry 194 (class 1259 OID 1866830)
-- Name: admin_packages; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_packages (
    id integer NOT NULL,
    name character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    is_addon smallint,
    price numeric(10,2)
);


ALTER TABLE dikodiko.admin_packages OWNER TO postgres;

--
-- TOC entry 195 (class 1259 OID 1866837)
-- Name: admin_packages_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_packages_id_seq OWNER TO postgres;

--
-- TOC entry 2859 (class 0 OID 0)
-- Dependencies: 195
-- Name: admin_packages_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_packages_id_seq OWNED BY dikodiko.admin_packages.id;


--
-- TOC entry 196 (class 1259 OID 1866839)
-- Name: admin_packages_payments; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_packages_payments (
    id integer NOT NULL,
    admin_payment_id integer,
    admin_package_id integer,
    start_date timestamp without time zone,
    end_date timestamp without time zone,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.admin_packages_payments OWNER TO postgres;

--
-- TOC entry 197 (class 1259 OID 1866843)
-- Name: admin_packages_payments_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_packages_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_packages_payments_id_seq OWNER TO postgres;

--
-- TOC entry 2860 (class 0 OID 0)
-- Dependencies: 197
-- Name: admin_packages_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_packages_payments_id_seq OWNED BY dikodiko.admin_packages_payments.id;


--
-- TOC entry 198 (class 1259 OID 1866845)
-- Name: admin_payments; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_payments (
    id integer NOT NULL,
    user_id integer,
    amount numeric(10,2),
    transaction_id character varying,
    method character varying,
    date date,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    admin_booking_id integer
);


ALTER TABLE dikodiko.admin_payments OWNER TO postgres;

--
-- TOC entry 199 (class 1259 OID 1866852)
-- Name: admin_payments_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_payments_id_seq OWNER TO postgres;

--
-- TOC entry 2861 (class 0 OID 0)
-- Dependencies: 199
-- Name: admin_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_payments_id_seq OWNED BY dikodiko.admin_payments.id;


--
-- TOC entry 200 (class 1259 OID 1866854)
-- Name: admin_sms_brought; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_sms_brought (
    id integer NOT NULL,
    admin_packages_payment_id integer,
    sms_provided integer,
    user_id integer,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone
);


ALTER TABLE dikodiko.admin_sms_brought OWNER TO postgres;

--
-- TOC entry 201 (class 1259 OID 1866858)
-- Name: admin_sms_brought_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_sms_brought_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_sms_brought_id_seq OWNER TO postgres;

--
-- TOC entry 2862 (class 0 OID 0)
-- Dependencies: 201
-- Name: admin_sms_brought_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_sms_brought_id_seq OWNED BY dikodiko.admin_sms_brought.id;


--
-- TOC entry 202 (class 1259 OID 1866860)
-- Name: admin_supports; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.admin_supports (
    id integer NOT NULL,
    topic character varying,
    details text,
    user_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.admin_supports OWNER TO postgres;

--
-- TOC entry 203 (class 1259 OID 1866867)
-- Name: admin_supports_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.admin_supports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.admin_supports_id_seq OWNER TO postgres;

--
-- TOC entry 2863 (class 0 OID 0)
-- Dependencies: 203
-- Name: admin_supports_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.admin_supports_id_seq OWNED BY dikodiko.admin_supports.id;


--
-- TOC entry 204 (class 1259 OID 1866869)
-- Name: api_requests; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.api_requests (
    id integer NOT NULL,
    content text,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    url character varying
);


ALTER TABLE dikodiko.api_requests OWNER TO postgres;

--
-- TOC entry 205 (class 1259 OID 1866876)
-- Name: api_requests_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.api_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.api_requests_id_seq OWNER TO postgres;

--
-- TOC entry 2864 (class 0 OID 0)
-- Dependencies: 205
-- Name: api_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.api_requests_id_seq OWNED BY dikodiko.api_requests.id;


--
-- TOC entry 206 (class 1259 OID 1866878)
-- Name: budget_payments; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.budget_payments (
    id integer NOT NULL,
    amount numeric,
    date date,
    method character varying,
    budget_id integer,
    created_by integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    note text
);


ALTER TABLE dikodiko.budget_payments OWNER TO postgres;

--
-- TOC entry 207 (class 1259 OID 1866885)
-- Name: budget_payments_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.budget_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.budget_payments_id_seq OWNER TO postgres;

--
-- TOC entry 2865 (class 0 OID 0)
-- Dependencies: 207
-- Name: budget_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.budget_payments_id_seq OWNED BY dikodiko.budget_payments.id;


--
-- TOC entry 208 (class 1259 OID 1866887)
-- Name: budgets; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.budgets (
    id integer NOT NULL,
    event_id integer,
    business_service_id integer,
    initial_price numeric(10,2),
    actual_price numeric(10,2),
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    paid_amount numeric,
    approved smallint DEFAULT 0,
    quantity integer
);


ALTER TABLE dikodiko.budgets OWNER TO postgres;

--
-- TOC entry 209 (class 1259 OID 1866895)
-- Name: budgets_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.budgets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.budgets_id_seq OWNER TO postgres;

--
-- TOC entry 2866 (class 0 OID 0)
-- Dependencies: 209
-- Name: budgets_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.budgets_id_seq OWNED BY dikodiko.budgets.id;


--
-- TOC entry 297 (class 1259 OID 1867901)
-- Name: business_ratings; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.business_ratings (
    id integer NOT NULL,
    event_id integer,
    user_id integer,
    business_id integer,
    body text,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    rating integer,
    reply text
);


ALTER TABLE dikodiko.business_ratings OWNER TO postgres;

--
-- TOC entry 296 (class 1259 OID 1867899)
-- Name: business_ratings_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.business_ratings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.business_ratings_id_seq OWNER TO postgres;

--
-- TOC entry 2867 (class 0 OID 0)
-- Dependencies: 296
-- Name: business_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.business_ratings_id_seq OWNED BY dikodiko.business_ratings.id;


--
-- TOC entry 210 (class 1259 OID 1866897)
-- Name: business_services; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.business_services (
    id integer NOT NULL,
    business_id integer,
    price numeric(10,2),
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone,
    name character varying,
    description text,
    photo text,
    offer integer,
    deleted_at timestamp without time zone,
    service_type character varying
);


ALTER TABLE dikodiko.business_services OWNER TO postgres;

--
-- TOC entry 2868 (class 0 OID 0)
-- Dependencies: 210
-- Name: COLUMN business_services.offer; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.business_services.offer IS 'business can put an offer in terms of percentage';


--
-- TOC entry 2869 (class 0 OID 0)
-- Dependencies: 210
-- Name: COLUMN business_services.service_type; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.business_services.service_type IS 'Tangible or non Tangible';


--
-- TOC entry 211 (class 1259 OID 1866904)
-- Name: business_services_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.business_services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.business_services_id_seq OWNER TO postgres;

--
-- TOC entry 2870 (class 0 OID 0)
-- Dependencies: 211
-- Name: business_services_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.business_services_id_seq OWNED BY dikodiko.business_services.id;


--
-- TOC entry 212 (class 1259 OID 1866906)
-- Name: business_types; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.business_types (
    id integer NOT NULL,
    name character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.business_types OWNER TO postgres;

--
-- TOC entry 213 (class 1259 OID 1866913)
-- Name: business_types_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.business_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.business_types_id_seq OWNER TO postgres;

--
-- TOC entry 2871 (class 0 OID 0)
-- Dependencies: 213
-- Name: business_types_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.business_types_id_seq OWNED BY dikodiko.business_types.id;


--
-- TOC entry 214 (class 1259 OID 1866915)
-- Name: businesses; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.businesses (
    id integer NOT NULL,
    user_id integer,
    address character varying,
    ward_id integer,
    descriptions character varying,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone,
    name character varying,
    about text,
    photo character varying,
    phone character varying,
    email character varying,
    website character varying,
    instagram character varying,
    facebook character varying,
    linkedin character varying,
    cover_page character varying,
    twitter character varying,
    legal_document character varying,
    business_type_id integer DEFAULT 1,
    status smallint,
    location character varying,
    country character varying,
    industry character varying,
    youtube character varying,
    service_id integer,
    total_staff integer DEFAULT 1,
    years_of_experience integer DEFAULT 1
);


ALTER TABLE dikodiko.businesses OWNER TO postgres;

--
-- TOC entry 2872 (class 0 OID 0)
-- Dependencies: 214
-- Name: COLUMN businesses.legal_document; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.businesses.legal_document IS 'Certificate of incorporation, TIN number, or Entrepreneur license copy';


--
-- TOC entry 2873 (class 0 OID 0)
-- Dependencies: 214
-- Name: COLUMN businesses.status; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.businesses.status IS '1=self registration,0=registered by client';


--
-- TOC entry 2874 (class 0 OID 0)
-- Dependencies: 214
-- Name: COLUMN businesses.service_id; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.businesses.service_id IS 'main service provided by the business';


--
-- TOC entry 215 (class 1259 OID 1866923)
-- Name: businesses_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.businesses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.businesses_id_seq OWNER TO postgres;

--
-- TOC entry 2875 (class 0 OID 0)
-- Dependencies: 215
-- Name: businesses_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.businesses_id_seq OWNED BY dikodiko.businesses.id;


--
-- TOC entry 216 (class 1259 OID 1866925)
-- Name: countries; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.countries (
    id integer NOT NULL,
    name character varying NOT NULL,
    country_code character varying,
    dialling_code character varying
);


ALTER TABLE dikodiko.countries OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 1866931)
-- Name: countries_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.countries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.countries_id_seq OWNER TO postgres;

--
-- TOC entry 2876 (class 0 OID 0)
-- Dependencies: 217
-- Name: countries_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.countries_id_seq OWNED BY dikodiko.countries.id;


--
-- TOC entry 218 (class 1259 OID 1866933)
-- Name: discount_requests; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.discount_requests (
    id integer NOT NULL,
    user_id integer,
    phone character varying,
    status smallint DEFAULT 0,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    type smallint DEFAULT 1
);


ALTER TABLE dikodiko.discount_requests OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 1866942)
-- Name: discount_requests_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.discount_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.discount_requests_id_seq OWNER TO postgres;

--
-- TOC entry 2877 (class 0 OID 0)
-- Dependencies: 219
-- Name: discount_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.discount_requests_id_seq OWNED BY dikodiko.discount_requests.id;


--
-- TOC entry 220 (class 1259 OID 1866944)
-- Name: districts; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.districts (
    id integer NOT NULL,
    name character varying,
    region_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.districts OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 1866951)
-- Name: districts_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.districts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.districts_id_seq OWNER TO postgres;

--
-- TOC entry 2878 (class 0 OID 0)
-- Dependencies: 221
-- Name: districts_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.districts_id_seq OWNED BY dikodiko.districts.id;


--
-- TOC entry 222 (class 1259 OID 1866953)
-- Name: error_logs; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.error_logs (
    id integer NOT NULL,
    error_message text,
    file character varying,
    route character varying,
    url character varying,
    error_instance character varying,
    request text,
    created_by integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    deleted_by integer
);


ALTER TABLE dikodiko.error_logs OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 1866960)
-- Name: error_logs_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.error_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.error_logs_id_seq OWNER TO postgres;

--
-- TOC entry 2879 (class 0 OID 0)
-- Dependencies: 223
-- Name: error_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.error_logs_id_seq OWNED BY dikodiko.error_logs.id;


--
-- TOC entry 224 (class 1259 OID 1866962)
-- Name: event_guest_categories; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.event_guest_categories (
    id integer NOT NULL,
    event_id integer,
    name character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.event_guest_categories OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 1866969)
-- Name: event_guest_categories_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.event_guest_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.event_guest_categories_id_seq OWNER TO postgres;

--
-- TOC entry 2880 (class 0 OID 0)
-- Dependencies: 225
-- Name: event_guest_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.event_guest_categories_id_seq OWNED BY dikodiko.event_guest_categories.id;


--
-- TOC entry 226 (class 1259 OID 1866971)
-- Name: events; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.events (
    id integer NOT NULL,
    name character varying,
    event_type_id integer,
    date timestamp without time zone,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone,
    status smallint DEFAULT 1,
    whatsapp_api_url character varying,
    whatsapp_token character varying,
    district_id integer,
    url character varying,
    uid character varying,
    location text,
    logo character varying,
    background text
);


ALTER TABLE dikodiko.events OWNER TO postgres;

--
-- TOC entry 2881 (class 0 OID 0)
-- Dependencies: 226
-- Name: COLUMN events.status; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.events.status IS '1=active, 0=not active';


--
-- TOC entry 227 (class 1259 OID 1866979)
-- Name: events_guests; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.events_guests (
    id integer NOT NULL,
    event_id integer,
    guest_name character varying,
    guest_email character varying,
    guest_email_verified_at timestamp(0) without time zone,
    guest_phone character varying,
    event_guest_category_id character varying,
    guest_pledge numeric(10,0),
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone,
    code character varying
);


ALTER TABLE dikodiko.events_guests OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 1866986)
-- Name: events_guests_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.events_guests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.events_guests_id_seq OWNER TO postgres;

--
-- TOC entry 2882 (class 0 OID 0)
-- Dependencies: 228
-- Name: events_guests_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.events_guests_id_seq OWNED BY dikodiko.events_guests.id;


--
-- TOC entry 229 (class 1259 OID 1866988)
-- Name: events_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.events_id_seq OWNER TO postgres;

--
-- TOC entry 2883 (class 0 OID 0)
-- Dependencies: 229
-- Name: events_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.events_id_seq OWNED BY dikodiko.events.id;


--
-- TOC entry 230 (class 1259 OID 1866990)
-- Name: events_types; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.events_types (
    id integer NOT NULL,
    name character varying,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone
);


ALTER TABLE dikodiko.events_types OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 1866997)
-- Name: events_types_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.events_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.events_types_id_seq OWNER TO postgres;

--
-- TOC entry 2884 (class 0 OID 0)
-- Dependencies: 231
-- Name: events_types_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.events_types_id_seq OWNED BY dikodiko.events_types.id;


--
-- TOC entry 293 (class 1259 OID 1867799)
-- Name: exhibitors; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.exhibitors (
    id integer NOT NULL,
    event_id integer,
    user_id integer,
    business_id integer,
    venue_id integer DEFAULT 1,
    booth_members text,
    exhibitor_type character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.exhibitors OWNER TO postgres;

--
-- TOC entry 292 (class 1259 OID 1867797)
-- Name: exhibitors_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.exhibitors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.exhibitors_id_seq OWNER TO postgres;

--
-- TOC entry 2885 (class 0 OID 0)
-- Dependencies: 292
-- Name: exhibitors_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.exhibitors_id_seq OWNED BY dikodiko.exhibitors.id;


--
-- TOC entry 232 (class 1259 OID 1866999)
-- Name: failed_jobs; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT now() NOT NULL
);


ALTER TABLE dikodiko.failed_jobs OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 1867006)
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.failed_jobs_id_seq OWNER TO postgres;

--
-- TOC entry 2886 (class 0 OID 0)
-- Dependencies: 233
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.failed_jobs_id_seq OWNED BY dikodiko.failed_jobs.id;


--
-- TOC entry 234 (class 1259 OID 1867008)
-- Name: file_albums; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.file_albums (
    id integer NOT NULL,
    name character varying,
    user_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    status smallint DEFAULT 1,
    business_id integer
);


ALTER TABLE dikodiko.file_albums OWNER TO postgres;

--
-- TOC entry 2887 (class 0 OID 0)
-- Dependencies: 234
-- Name: COLUMN file_albums.status; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.file_albums.status IS 'This separate between products and other documents, so by default, all products will have status =1 but other documents will have 0 status and they will are pre=defined';


--
-- TOC entry 235 (class 1259 OID 1867015)
-- Name: file_albums_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.file_albums_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.file_albums_id_seq OWNER TO postgres;

--
-- TOC entry 2888 (class 0 OID 0)
-- Dependencies: 235
-- Name: file_albums_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.file_albums_id_seq OWNED BY dikodiko.file_albums.id;


--
-- TOC entry 236 (class 1259 OID 1867017)
-- Name: files; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.files (
    id integer NOT NULL,
    mime text,
    name character varying,
    size integer,
    caption text,
    url character varying,
    file_album_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.files OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 1867024)
-- Name: files_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.files_id_seq OWNER TO postgres;

--
-- TOC entry 2889 (class 0 OID 0)
-- Dependencies: 237
-- Name: files_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.files_id_seq OWNED BY dikodiko.files.id;


--
-- TOC entry 238 (class 1259 OID 1867026)
-- Name: live_attendees; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.live_attendees (
    id integer NOT NULL,
    events_guest_id integer,
    device character varying,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.live_attendees OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 1867032)
-- Name: live_attendances_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.live_attendances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.live_attendances_id_seq OWNER TO postgres;

--
-- TOC entry 2890 (class 0 OID 0)
-- Dependencies: 239
-- Name: live_attendances_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.live_attendances_id_seq OWNED BY dikodiko.live_attendees.id;


--
-- TOC entry 240 (class 1259 OID 1867034)
-- Name: login_locations; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.login_locations (
    id integer NOT NULL,
    ip character varying,
    city character varying,
    region character varying,
    country character varying,
    latitude character varying,
    longtude character varying,
    timezone character varying,
    user_id integer,
    continent character varying,
    currency_code character varying,
    currency_symbol character varying,
    currency_convert character varying,
    location_radius_accuracy character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    action character varying
);


ALTER TABLE dikodiko.login_locations OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 1867041)
-- Name: login_locations_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.login_locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.login_locations_id_seq OWNER TO postgres;

--
-- TOC entry 2891 (class 0 OID 0)
-- Dependencies: 241
-- Name: login_locations_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.login_locations_id_seq OWNED BY dikodiko.login_locations.id;


--
-- TOC entry 242 (class 1259 OID 1867043)
-- Name: logs; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.logs (
    id integer NOT NULL,
    url character varying,
    user_agent character varying,
    platform character varying,
    platform_name character varying,
    source character varying,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone,
    user_id integer,
    country character varying,
    city character varying,
    region character varying,
    isp character varying,
    controller character varying,
    method character varying,
    is_ajax smallint DEFAULT 0,
    request json
);


ALTER TABLE dikodiko.logs OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 1867051)
-- Name: logs_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.logs_id_seq OWNER TO postgres;

--
-- TOC entry 2892 (class 0 OID 0)
-- Dependencies: 243
-- Name: logs_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.logs_id_seq OWNED BY dikodiko.logs.id;


--
-- TOC entry 244 (class 1259 OID 1867053)
-- Name: messages; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.messages (
    id integer NOT NULL,
    body text,
    subject character varying,
    user_id integer,
    status integer DEFAULT 0,
    phone character varying,
    email character varying,
    type integer DEFAULT 1,
    sms_count integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    to_id integer,
    is_read smallint DEFAULT 0
);


ALTER TABLE dikodiko.messages OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 1867062)
-- Name: messages_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.messages_id_seq OWNER TO postgres;

--
-- TOC entry 2893 (class 0 OID 0)
-- Dependencies: 245
-- Name: messages_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.messages_id_seq OWNED BY dikodiko.messages.id;


--
-- TOC entry 246 (class 1259 OID 1867064)
-- Name: messages_sentby; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.messages_sentby (
    id integer NOT NULL,
    message_id integer,
    channel character varying,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone,
    return_code character varying,
    status integer DEFAULT 0,
    device character varying
);


ALTER TABLE dikodiko.messages_sentby OWNER TO postgres;

--
-- TOC entry 2894 (class 0 OID 0)
-- Dependencies: 246
-- Name: COLUMN messages_sentby.return_code; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.messages_sentby.return_code IS 'what API returns after sending';


--
-- TOC entry 247 (class 1259 OID 1867072)
-- Name: messages_sentby_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.messages_sentby_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.messages_sentby_id_seq OWNER TO postgres;

--
-- TOC entry 2895 (class 0 OID 0)
-- Dependencies: 247
-- Name: messages_sentby_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.messages_sentby_id_seq OWNED BY dikodiko.messages_sentby.id;


--
-- TOC entry 248 (class 1259 OID 1867074)
-- Name: migrations; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE dikodiko.migrations OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 1867077)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.migrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.migrations_id_seq OWNER TO postgres;

--
-- TOC entry 2896 (class 0 OID 0)
-- Dependencies: 249
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.migrations_id_seq OWNED BY dikodiko.migrations.id;


--
-- TOC entry 295 (class 1259 OID 1867829)
-- Name: news; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.news (
    id integer NOT NULL,
    event_id integer,
    user_id integer,
    business_id integer,
    title character varying,
    body text,
    viewers text,
    photo text,
    category character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.news OWNER TO postgres;

--
-- TOC entry 294 (class 1259 OID 1867827)
-- Name: news_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.news_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.news_id_seq OWNER TO postgres;

--
-- TOC entry 2897 (class 0 OID 0)
-- Dependencies: 294
-- Name: news_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.news_id_seq OWNED BY dikodiko.news.id;


--
-- TOC entry 250 (class 1259 OID 1867079)
-- Name: page_viewers; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.page_viewers (
    id integer NOT NULL,
    user_id integer,
    business_id integer,
    device character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.page_viewers OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 1867086)
-- Name: page_viewers_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.page_viewers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.page_viewers_id_seq OWNER TO postgres;

--
-- TOC entry 2898 (class 0 OID 0)
-- Dependencies: 251
-- Name: page_viewers_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.page_viewers_id_seq OWNED BY dikodiko.page_viewers.id;


--
-- TOC entry 252 (class 1259 OID 1867088)
-- Name: password_resets; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.password_resets (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE dikodiko.password_resets OWNER TO postgres;

--
-- TOC entry 253 (class 1259 OID 1867094)
-- Name: payments; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.payments (
    id integer NOT NULL,
    events_guests_id integer,
    amount numeric(10,2),
    transaction_id character varying,
    method character varying,
    date date,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.payments OWNER TO postgres;

--
-- TOC entry 254 (class 1259 OID 1867101)
-- Name: payments_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.payments_id_seq OWNER TO postgres;

--
-- TOC entry 2899 (class 0 OID 0)
-- Dependencies: 254
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.payments_id_seq OWNED BY dikodiko.payments.id;


--
-- TOC entry 255 (class 1259 OID 1867103)
-- Name: promotions; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.promotions (
    id integer NOT NULL,
    uid character varying,
    booking_id integer,
    business_service_id integer,
    promotion_type character varying,
    total_users integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.promotions OWNER TO postgres;

--
-- TOC entry 256 (class 1259 OID 1867110)
-- Name: promotions_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.promotions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.promotions_id_seq OWNER TO postgres;

--
-- TOC entry 2900 (class 0 OID 0)
-- Dependencies: 256
-- Name: promotions_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.promotions_id_seq OWNED BY dikodiko.promotions.id;


--
-- TOC entry 257 (class 1259 OID 1867112)
-- Name: promotions_payments; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.promotions_payments (
    id integer NOT NULL,
    promotion_id integer,
    payment_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.promotions_payments OWNER TO postgres;

--
-- TOC entry 258 (class 1259 OID 1867116)
-- Name: promotions_payments_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.promotions_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.promotions_payments_id_seq OWNER TO postgres;

--
-- TOC entry 2901 (class 0 OID 0)
-- Dependencies: 258
-- Name: promotions_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.promotions_payments_id_seq OWNED BY dikodiko.promotions_payments.id;


--
-- TOC entry 259 (class 1259 OID 1867118)
-- Name: promotions_reaches; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.promotions_reaches (
    id integer NOT NULL,
    user_id integer,
    promotion_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.promotions_reaches OWNER TO postgres;

--
-- TOC entry 260 (class 1259 OID 1867122)
-- Name: promotions_reaches_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.promotions_reaches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.promotions_reaches_id_seq OWNER TO postgres;

--
-- TOC entry 2902 (class 0 OID 0)
-- Dependencies: 260
-- Name: promotions_reaches_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.promotions_reaches_id_seq OWNED BY dikodiko.promotions_reaches.id;


--
-- TOC entry 261 (class 1259 OID 1867124)
-- Name: regions; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.regions (
    id integer NOT NULL,
    name character varying,
    country_id integer DEFAULT 1,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.regions OWNER TO postgres;

--
-- TOC entry 262 (class 1259 OID 1867132)
-- Name: regions_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.regions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.regions_id_seq OWNER TO postgres;

--
-- TOC entry 2903 (class 0 OID 0)
-- Dependencies: 262
-- Name: regions_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.regions_id_seq OWNED BY dikodiko.regions.id;


--
-- TOC entry 263 (class 1259 OID 1867134)
-- Name: reminders; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.reminders (
    id integer NOT NULL,
    user_id integer,
    date timestamp without time zone,
    "time" time without time zone,
    title character varying,
    is_repeated smallint DEFAULT 0,
    days character varying,
    message text,
    type smallint DEFAULT 0,
    event_guest_category_id integer,
    users character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    last_date timestamp without time zone,
    channels character varying,
    criteria integer
);


ALTER TABLE dikodiko.reminders OWNER TO postgres;

--
-- TOC entry 2904 (class 0 OID 0)
-- Dependencies: 263
-- Name: COLUMN reminders.type; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.reminders.type IS '0=both, 1=sms, 2=email';


--
-- TOC entry 2905 (class 0 OID 0)
-- Dependencies: 263
-- Name: COLUMN reminders.users; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.reminders.users IS 'List of users to receive such message separated by comma';


--
-- TOC entry 2906 (class 0 OID 0)
-- Dependencies: 263
-- Name: COLUMN reminders.channels; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.reminders.channels IS 'This message will be sent by which channel';


--
-- TOC entry 2907 (class 0 OID 0)
-- Dependencies: 263
-- Name: COLUMN reminders.criteria; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.reminders.criteria IS '"1">All Guests
"3">Full Paid Guest
"4">Non Paid Guest
"5">Partially Paid Guest';


--
-- TOC entry 264 (class 1259 OID 1867143)
-- Name: reminders_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.reminders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.reminders_id_seq OWNER TO postgres;

--
-- TOC entry 2908 (class 0 OID 0)
-- Dependencies: 264
-- Name: reminders_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.reminders_id_seq OWNED BY dikodiko.reminders.id;


--
-- TOC entry 287 (class 1259 OID 1867715)
-- Name: schedules; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.schedules (
    id integer NOT NULL,
    event_id integer,
    activity_start_time timestamp without time zone,
    activity_end_time timestamp without time zone,
    title character varying,
    body text,
    user_id integer,
    venue_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.schedules OWNER TO postgres;

--
-- TOC entry 286 (class 1259 OID 1867713)
-- Name: schedules_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.schedules_id_seq OWNER TO postgres;

--
-- TOC entry 2909 (class 0 OID 0)
-- Dependencies: 286
-- Name: schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.schedules_id_seq OWNED BY dikodiko.schedules.id;


--
-- TOC entry 265 (class 1259 OID 1867145)
-- Name: services; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.services (
    id integer NOT NULL,
    name character varying,
    descriptions text,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone
);


ALTER TABLE dikodiko.services OWNER TO postgres;

--
-- TOC entry 266 (class 1259 OID 1867152)
-- Name: services_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.services_id_seq OWNER TO postgres;

--
-- TOC entry 2910 (class 0 OID 0)
-- Dependencies: 266
-- Name: services_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.services_id_seq OWNED BY dikodiko.services.id;


--
-- TOC entry 289 (class 1259 OID 1867744)
-- Name: speakers; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.speakers (
    id integer NOT NULL,
    user_id integer,
    event_id integer,
    schedule_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.speakers OWNER TO postgres;

--
-- TOC entry 288 (class 1259 OID 1867742)
-- Name: speakers_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.speakers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.speakers_id_seq OWNER TO postgres;

--
-- TOC entry 2911 (class 0 OID 0)
-- Dependencies: 288
-- Name: speakers_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.speakers_id_seq OWNED BY dikodiko.speakers.id;


--
-- TOC entry 291 (class 1259 OID 1867770)
-- Name: sponsors; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.sponsors (
    id integer NOT NULL,
    event_id integer,
    user_id integer,
    business_id integer,
    sponsorship_type character varying,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.sponsors OWNER TO postgres;

--
-- TOC entry 290 (class 1259 OID 1867768)
-- Name: sponsors_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.sponsors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.sponsors_id_seq OWNER TO postgres;

--
-- TOC entry 2912 (class 0 OID 0)
-- Dependencies: 290
-- Name: sponsors_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.sponsors_id_seq OWNED BY dikodiko.sponsors.id;


--
-- TOC entry 267 (class 1259 OID 1867154)
-- Name: telegram_users; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.telegram_users (
    id integer NOT NULL,
    telegram_id integer NOT NULL,
    events_guest_id integer,
    created_at timestamp without time zone DEFAULT now(),
    phone_number character varying
);


ALTER TABLE dikodiko.telegram_users OWNER TO postgres;

--
-- TOC entry 268 (class 1259 OID 1867161)
-- Name: telegram_users_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.telegram_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.telegram_users_id_seq OWNER TO postgres;

--
-- TOC entry 2913 (class 0 OID 0)
-- Dependencies: 268
-- Name: telegram_users_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.telegram_users_id_seq OWNED BY dikodiko.telegram_users.id;


--
-- TOC entry 269 (class 1259 OID 1867163)
-- Name: users_keys; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.users_keys (
    id integer NOT NULL,
    type character varying,
    api_key character varying,
    api_secret character varying,
    user_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    device character varying,
    others character varying,
    last_active timestamp without time zone
);


ALTER TABLE dikodiko.users_keys OWNER TO postgres;

--
-- TOC entry 270 (class 1259 OID 1867170)
-- Name: user_keys_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.user_keys_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.user_keys_id_seq OWNER TO postgres;

--
-- TOC entry 2914 (class 0 OID 0)
-- Dependencies: 270
-- Name: user_keys_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.user_keys_id_seq OWNED BY dikodiko.users_keys.id;


--
-- TOC entry 271 (class 1259 OID 1867172)
-- Name: user_types; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.user_types (
    id integer NOT NULL,
    name character varying,
    description text,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.user_types OWNER TO postgres;

--
-- TOC entry 272 (class 1259 OID 1867179)
-- Name: user_types_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.user_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.user_types_id_seq OWNER TO postgres;

--
-- TOC entry 2915 (class 0 OID 0)
-- Dependencies: 272
-- Name: user_types_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.user_types_id_seq OWNED BY dikodiko.user_types.id;


--
-- TOC entry 273 (class 1259 OID 1867181)
-- Name: users; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    phone character varying,
    user_type_id integer DEFAULT 1,
    verified smallint DEFAULT 0,
    verify_code character varying,
    designation character varying,
    photo character varying DEFAULT 'http://localhost/eventapp/resources/assets/images/users/user-1.jpg'::character varying,
    last_seen timestamp without time zone,
    country character varying,
    instagram character varying,
    twitter character varying,
    linkedin character varying,
    website character varying,
    show_phone smallint DEFAULT 0,
    show_email smallint DEFAULT 1,
    facebook character varying,
    about text
);


ALTER TABLE dikodiko.users OWNER TO postgres;

--
-- TOC entry 2916 (class 0 OID 0)
-- Dependencies: 273
-- Name: COLUMN users.verified; Type: COMMENT; Schema: dikodiko; Owner: postgres
--

COMMENT ON COLUMN dikodiko.users.verified IS '0=not verified, 1=verified by whatsapp code, 2=veriffy by email link';


--
-- TOC entry 274 (class 1259 OID 1867189)
-- Name: users_events; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.users_events (
    id integer NOT NULL,
    user_id integer,
    event_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    status smallint DEFAULT 1
);


ALTER TABLE dikodiko.users_events OWNER TO postgres;

--
-- TOC entry 275 (class 1259 OID 1867194)
-- Name: users_events_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.users_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.users_events_id_seq OWNER TO postgres;

--
-- TOC entry 2917 (class 0 OID 0)
-- Dependencies: 275
-- Name: users_events_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.users_events_id_seq OWNED BY dikodiko.users_events.id;


--
-- TOC entry 276 (class 1259 OID 1867196)
-- Name: users_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.users_id_seq OWNER TO postgres;

--
-- TOC entry 2918 (class 0 OID 0)
-- Dependencies: 276
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.users_id_seq OWNED BY dikodiko.users.id;


--
-- TOC entry 277 (class 1259 OID 1867198)
-- Name: users_sms_status; Type: VIEW; Schema: dikodiko; Owner: postgres
--

CREATE VIEW dikodiko.users_sms_status AS
 SELECT a.user_id,
    (sum(c.sms_provided) - sum(a.sms_count)) AS message_left
   FROM ((dikodiko.messages a
     JOIN dikodiko.messages_sentby b ON ((a.id = b.message_id)))
     JOIN dikodiko.admin_sms_brought c ON ((c.user_id = a.user_id)))
  WHERE ((b.channel)::text = 'quick-sms'::text)
  GROUP BY a.user_id;


ALTER TABLE dikodiko.users_sms_status OWNER TO postgres;

--
-- TOC entry 285 (class 1259 OID 1867701)
-- Name: venues; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.venues (
    id integer NOT NULL,
    title character varying,
    body text,
    address text,
    cordinates text,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


ALTER TABLE dikodiko.venues OWNER TO postgres;

--
-- TOC entry 284 (class 1259 OID 1867699)
-- Name: venues_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.venues_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.venues_id_seq OWNER TO postgres;

--
-- TOC entry 2919 (class 0 OID 0)
-- Dependencies: 284
-- Name: venues_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.venues_id_seq OWNED BY dikodiko.venues.id;


--
-- TOC entry 278 (class 1259 OID 1867203)
-- Name: wards; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.wards (
    id integer NOT NULL,
    name character varying,
    district_id integer,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.wards OWNER TO postgres;

--
-- TOC entry 279 (class 1259 OID 1867210)
-- Name: wards_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.wards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.wards_id_seq OWNER TO postgres;

--
-- TOC entry 2920 (class 0 OID 0)
-- Dependencies: 279
-- Name: wards_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.wards_id_seq OWNED BY dikodiko.wards.id;


--
-- TOC entry 280 (class 1259 OID 1867212)
-- Name: whatsapp_log; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.whatsapp_log (
    id integer NOT NULL,
    content text,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.whatsapp_log OWNER TO postgres;

--
-- TOC entry 281 (class 1259 OID 1867219)
-- Name: whatsapp_log_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.whatsapp_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.whatsapp_log_id_seq OWNER TO postgres;

--
-- TOC entry 2921 (class 0 OID 0)
-- Dependencies: 281
-- Name: whatsapp_log_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.whatsapp_log_id_seq OWNED BY dikodiko.whatsapp_log.id;


--
-- TOC entry 282 (class 1259 OID 1867221)
-- Name: whatsapp_message_logs; Type: TABLE; Schema: dikodiko; Owner: postgres
--

CREATE TABLE dikodiko.whatsapp_message_logs (
    id integer NOT NULL,
    "chatId" character varying,
    body text,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone
);


ALTER TABLE dikodiko.whatsapp_message_logs OWNER TO postgres;

--
-- TOC entry 283 (class 1259 OID 1867228)
-- Name: whatsapp_message_logs_id_seq; Type: SEQUENCE; Schema: dikodiko; Owner: postgres
--

CREATE SEQUENCE dikodiko.whatsapp_message_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dikodiko.whatsapp_message_logs_id_seq OWNER TO postgres;

--
-- TOC entry 2922 (class 0 OID 0)
-- Dependencies: 283
-- Name: whatsapp_message_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: dikodiko; Owner: postgres
--

ALTER SEQUENCE dikodiko.whatsapp_message_logs_id_seq OWNED BY dikodiko.whatsapp_message_logs.id;


--
-- TOC entry 2386 (class 2604 OID 1867230)
-- Name: admin_bookings id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_bookings ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_bookings_id_seq'::regclass);


--
-- TOC entry 2388 (class 2604 OID 1867231)
-- Name: admin_feature_packages id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_feature_packages ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_feature_packages_id_seq'::regclass);


--
-- TOC entry 2390 (class 2604 OID 1867232)
-- Name: admin_features id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_features ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_features_id_seq'::regclass);


--
-- TOC entry 2393 (class 2604 OID 1867233)
-- Name: admin_integration_requests id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_integration_requests ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_integration_requests_id_seq'::regclass);


--
-- TOC entry 2395 (class 2604 OID 1867234)
-- Name: admin_packages id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_packages ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_packages_id_seq'::regclass);


--
-- TOC entry 2397 (class 2604 OID 1867235)
-- Name: admin_packages_payments id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_packages_payments ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_packages_payments_id_seq'::regclass);


--
-- TOC entry 2399 (class 2604 OID 1867236)
-- Name: admin_payments id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_payments ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_payments_id_seq'::regclass);


--
-- TOC entry 2401 (class 2604 OID 1867237)
-- Name: admin_sms_brought id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_sms_brought ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_sms_brought_id_seq'::regclass);


--
-- TOC entry 2403 (class 2604 OID 1867238)
-- Name: admin_supports id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_supports ALTER COLUMN id SET DEFAULT nextval('dikodiko.admin_supports_id_seq'::regclass);


--
-- TOC entry 2405 (class 2604 OID 1867239)
-- Name: api_requests id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.api_requests ALTER COLUMN id SET DEFAULT nextval('dikodiko.api_requests_id_seq'::regclass);


--
-- TOC entry 2407 (class 2604 OID 1867240)
-- Name: budget_payments id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budget_payments ALTER COLUMN id SET DEFAULT nextval('dikodiko.budget_payments_id_seq'::regclass);


--
-- TOC entry 2410 (class 2604 OID 1867241)
-- Name: budgets id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budgets ALTER COLUMN id SET DEFAULT nextval('dikodiko.budgets_id_seq'::regclass);


--
-- TOC entry 2513 (class 2604 OID 1867904)
-- Name: business_ratings id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_ratings ALTER COLUMN id SET DEFAULT nextval('dikodiko.business_ratings_id_seq'::regclass);


--
-- TOC entry 2412 (class 2604 OID 1867242)
-- Name: business_services id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_services ALTER COLUMN id SET DEFAULT nextval('dikodiko.business_services_id_seq'::regclass);


--
-- TOC entry 2414 (class 2604 OID 1867243)
-- Name: business_types id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_types ALTER COLUMN id SET DEFAULT nextval('dikodiko.business_types_id_seq'::regclass);


--
-- TOC entry 2417 (class 2604 OID 1867244)
-- Name: businesses id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.businesses ALTER COLUMN id SET DEFAULT nextval('dikodiko.businesses_id_seq'::regclass);


--
-- TOC entry 2420 (class 2604 OID 1867245)
-- Name: countries id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.countries ALTER COLUMN id SET DEFAULT nextval('dikodiko.countries_id_seq'::regclass);


--
-- TOC entry 2424 (class 2604 OID 1867246)
-- Name: discount_requests id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.discount_requests ALTER COLUMN id SET DEFAULT nextval('dikodiko.discount_requests_id_seq'::regclass);


--
-- TOC entry 2426 (class 2604 OID 1867247)
-- Name: districts id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.districts ALTER COLUMN id SET DEFAULT nextval('dikodiko.districts_id_seq'::regclass);


--
-- TOC entry 2428 (class 2604 OID 1867248)
-- Name: error_logs id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.error_logs ALTER COLUMN id SET DEFAULT nextval('dikodiko.error_logs_id_seq'::regclass);


--
-- TOC entry 2430 (class 2604 OID 1867249)
-- Name: event_guest_categories id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.event_guest_categories ALTER COLUMN id SET DEFAULT nextval('dikodiko.event_guest_categories_id_seq'::regclass);


--
-- TOC entry 2433 (class 2604 OID 1867250)
-- Name: events id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events ALTER COLUMN id SET DEFAULT nextval('dikodiko.events_id_seq'::regclass);


--
-- TOC entry 2435 (class 2604 OID 1867251)
-- Name: events_guests id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events_guests ALTER COLUMN id SET DEFAULT nextval('dikodiko.events_guests_id_seq'::regclass);


--
-- TOC entry 2437 (class 2604 OID 1867252)
-- Name: events_types id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events_types ALTER COLUMN id SET DEFAULT nextval('dikodiko.events_types_id_seq'::regclass);


--
-- TOC entry 2508 (class 2604 OID 1867802)
-- Name: exhibitors id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.exhibitors ALTER COLUMN id SET DEFAULT nextval('dikodiko.exhibitors_id_seq'::regclass);


--
-- TOC entry 2439 (class 2604 OID 1867253)
-- Name: failed_jobs id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.failed_jobs ALTER COLUMN id SET DEFAULT nextval('dikodiko.failed_jobs_id_seq'::regclass);


--
-- TOC entry 2441 (class 2604 OID 1867254)
-- Name: file_albums id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.file_albums ALTER COLUMN id SET DEFAULT nextval('dikodiko.file_albums_id_seq'::regclass);


--
-- TOC entry 2444 (class 2604 OID 1867255)
-- Name: files id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.files ALTER COLUMN id SET DEFAULT nextval('dikodiko.files_id_seq'::regclass);


--
-- TOC entry 2445 (class 2604 OID 1867256)
-- Name: live_attendees id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.live_attendees ALTER COLUMN id SET DEFAULT nextval('dikodiko.live_attendances_id_seq'::regclass);


--
-- TOC entry 2447 (class 2604 OID 1867257)
-- Name: login_locations id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.login_locations ALTER COLUMN id SET DEFAULT nextval('dikodiko.login_locations_id_seq'::regclass);


--
-- TOC entry 2450 (class 2604 OID 1867258)
-- Name: logs id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.logs ALTER COLUMN id SET DEFAULT nextval('dikodiko.logs_id_seq'::regclass);


--
-- TOC entry 2454 (class 2604 OID 1867259)
-- Name: messages id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.messages ALTER COLUMN id SET DEFAULT nextval('dikodiko.messages_id_seq'::regclass);


--
-- TOC entry 2458 (class 2604 OID 1867260)
-- Name: messages_sentby id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.messages_sentby ALTER COLUMN id SET DEFAULT nextval('dikodiko.messages_sentby_id_seq'::regclass);


--
-- TOC entry 2459 (class 2604 OID 1867261)
-- Name: migrations id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.migrations ALTER COLUMN id SET DEFAULT nextval('dikodiko.migrations_id_seq'::regclass);


--
-- TOC entry 2511 (class 2604 OID 1867832)
-- Name: news id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.news ALTER COLUMN id SET DEFAULT nextval('dikodiko.news_id_seq'::regclass);


--
-- TOC entry 2461 (class 2604 OID 1867262)
-- Name: page_viewers id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.page_viewers ALTER COLUMN id SET DEFAULT nextval('dikodiko.page_viewers_id_seq'::regclass);


--
-- TOC entry 2463 (class 2604 OID 1867263)
-- Name: payments id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.payments ALTER COLUMN id SET DEFAULT nextval('dikodiko.payments_id_seq'::regclass);


--
-- TOC entry 2465 (class 2604 OID 1867264)
-- Name: promotions id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions ALTER COLUMN id SET DEFAULT nextval('dikodiko.promotions_id_seq'::regclass);


--
-- TOC entry 2467 (class 2604 OID 1867265)
-- Name: promotions_payments id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_payments ALTER COLUMN id SET DEFAULT nextval('dikodiko.promotions_payments_id_seq'::regclass);


--
-- TOC entry 2469 (class 2604 OID 1867266)
-- Name: promotions_reaches id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_reaches ALTER COLUMN id SET DEFAULT nextval('dikodiko.promotions_reaches_id_seq'::regclass);


--
-- TOC entry 2472 (class 2604 OID 1867267)
-- Name: regions id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.regions ALTER COLUMN id SET DEFAULT nextval('dikodiko.regions_id_seq'::regclass);


--
-- TOC entry 2476 (class 2604 OID 1867268)
-- Name: reminders id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.reminders ALTER COLUMN id SET DEFAULT nextval('dikodiko.reminders_id_seq'::regclass);


--
-- TOC entry 2502 (class 2604 OID 1867718)
-- Name: schedules id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.schedules ALTER COLUMN id SET DEFAULT nextval('dikodiko.schedules_id_seq'::regclass);


--
-- TOC entry 2478 (class 2604 OID 1867269)
-- Name: services id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.services ALTER COLUMN id SET DEFAULT nextval('dikodiko.services_id_seq'::regclass);


--
-- TOC entry 2504 (class 2604 OID 1867747)
-- Name: speakers id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.speakers ALTER COLUMN id SET DEFAULT nextval('dikodiko.speakers_id_seq'::regclass);


--
-- TOC entry 2506 (class 2604 OID 1867773)
-- Name: sponsors id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.sponsors ALTER COLUMN id SET DEFAULT nextval('dikodiko.sponsors_id_seq'::regclass);


--
-- TOC entry 2480 (class 2604 OID 1867270)
-- Name: telegram_users id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.telegram_users ALTER COLUMN id SET DEFAULT nextval('dikodiko.telegram_users_id_seq'::regclass);


--
-- TOC entry 2484 (class 2604 OID 1867271)
-- Name: user_types id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.user_types ALTER COLUMN id SET DEFAULT nextval('dikodiko.user_types_id_seq'::regclass);


--
-- TOC entry 2487 (class 2604 OID 1867272)
-- Name: users id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users ALTER COLUMN id SET DEFAULT nextval('dikodiko.users_id_seq'::regclass);


--
-- TOC entry 2493 (class 2604 OID 1867273)
-- Name: users_events id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_events ALTER COLUMN id SET DEFAULT nextval('dikodiko.users_events_id_seq'::regclass);


--
-- TOC entry 2482 (class 2604 OID 1867274)
-- Name: users_keys id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_keys ALTER COLUMN id SET DEFAULT nextval('dikodiko.user_keys_id_seq'::regclass);


--
-- TOC entry 2500 (class 2604 OID 1867704)
-- Name: venues id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.venues ALTER COLUMN id SET DEFAULT nextval('dikodiko.venues_id_seq'::regclass);


--
-- TOC entry 2495 (class 2604 OID 1867275)
-- Name: wards id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.wards ALTER COLUMN id SET DEFAULT nextval('dikodiko.wards_id_seq'::regclass);


--
-- TOC entry 2497 (class 2604 OID 1867276)
-- Name: whatsapp_log id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.whatsapp_log ALTER COLUMN id SET DEFAULT nextval('dikodiko.whatsapp_log_id_seq'::regclass);


--
-- TOC entry 2499 (class 2604 OID 1867277)
-- Name: whatsapp_message_logs id; Type: DEFAULT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.whatsapp_message_logs ALTER COLUMN id SET DEFAULT nextval('dikodiko.whatsapp_message_logs_id_seq'::regclass);


--
-- TOC entry 2516 (class 2606 OID 1867279)
-- Name: admin_bookings admin_bookings_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_bookings
    ADD CONSTRAINT admin_bookings_id_primary PRIMARY KEY (id);


--
-- TOC entry 2518 (class 2606 OID 1867281)
-- Name: admin_bookings admin_bookings_order_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_bookings
    ADD CONSTRAINT admin_bookings_order_id_unique UNIQUE (order_id);


--
-- TOC entry 2520 (class 2606 OID 1867283)
-- Name: admin_bookings admin_bookings_reference_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_bookings
    ADD CONSTRAINT admin_bookings_reference_unique UNIQUE (reference);


--
-- TOC entry 2522 (class 2606 OID 1867285)
-- Name: admin_bookings admin_bookings_token_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_bookings
    ADD CONSTRAINT admin_bookings_token_unique UNIQUE (token);


--
-- TOC entry 2525 (class 2606 OID 1867287)
-- Name: admin_feature_packages admin_feature_packages_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_feature_packages
    ADD CONSTRAINT admin_feature_packages_id_primary PRIMARY KEY (id);


--
-- TOC entry 2527 (class 2606 OID 1867289)
-- Name: admin_features admin_features_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_features
    ADD CONSTRAINT admin_features_id_primary PRIMARY KEY (id);


--
-- TOC entry 2529 (class 2606 OID 1867291)
-- Name: admin_integration_requests admin_integration_requests_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_integration_requests
    ADD CONSTRAINT admin_integration_requests_id_primary PRIMARY KEY (id);


--
-- TOC entry 2531 (class 2606 OID 1867293)
-- Name: admin_packages admin_packages_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_packages
    ADD CONSTRAINT admin_packages_id_primary PRIMARY KEY (id);


--
-- TOC entry 2533 (class 2606 OID 1867295)
-- Name: admin_packages_payments admin_packages_payments_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_packages_payments
    ADD CONSTRAINT admin_packages_payments_id_primary PRIMARY KEY (id);


--
-- TOC entry 2535 (class 2606 OID 1867297)
-- Name: admin_payments admin_payments_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_payments
    ADD CONSTRAINT admin_payments_id_primary PRIMARY KEY (id);


--
-- TOC entry 2538 (class 2606 OID 1867299)
-- Name: admin_sms_brought admin_sms_brought_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_sms_brought
    ADD CONSTRAINT admin_sms_brought_id_primary PRIMARY KEY (id);


--
-- TOC entry 2540 (class 2606 OID 1867301)
-- Name: admin_supports admin_support_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_supports
    ADD CONSTRAINT admin_support_id_primary PRIMARY KEY (id);


--
-- TOC entry 2542 (class 2606 OID 1867303)
-- Name: api_requests api_requests_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.api_requests
    ADD CONSTRAINT api_requests_pkey PRIMARY KEY (id);


--
-- TOC entry 2546 (class 2606 OID 1867305)
-- Name: budgets budget_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budgets
    ADD CONSTRAINT budget_id_primary PRIMARY KEY (id);


--
-- TOC entry 2544 (class 2606 OID 1867307)
-- Name: budget_payments budget_payments_id; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budget_payments
    ADD CONSTRAINT budget_payments_id PRIMARY KEY (id);


--
-- TOC entry 2548 (class 2606 OID 1867309)
-- Name: budgets budget_users_events_id_service_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budgets
    ADD CONSTRAINT budget_users_events_id_service_id_unique UNIQUE (event_id, business_service_id);


--
-- TOC entry 2663 (class 2606 OID 1867910)
-- Name: business_ratings business_ratings_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_ratings
    ADD CONSTRAINT business_ratings_id_primary PRIMARY KEY (id);


--
-- TOC entry 2665 (class 2606 OID 1867912)
-- Name: business_ratings business_ratings_user_id_business_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_ratings
    ADD CONSTRAINT business_ratings_user_id_business_id_unique UNIQUE (user_id, business_id);


--
-- TOC entry 2550 (class 2606 OID 1867313)
-- Name: business_services business_services_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_services
    ADD CONSTRAINT business_services_id_primary PRIMARY KEY (id);


--
-- TOC entry 2552 (class 2606 OID 1867315)
-- Name: business_types business_types_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_types
    ADD CONSTRAINT business_types_pkey PRIMARY KEY (id);


--
-- TOC entry 2554 (class 2606 OID 1867317)
-- Name: businesses businesses_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.businesses
    ADD CONSTRAINT businesses_id_primary PRIMARY KEY (id);


--
-- TOC entry 2556 (class 2606 OID 1867319)
-- Name: businesses businesses_user_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.businesses
    ADD CONSTRAINT businesses_user_id_unique UNIQUE (user_id);


--
-- TOC entry 2559 (class 2606 OID 1867321)
-- Name: countries countries_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.countries
    ADD CONSTRAINT countries_id_primary PRIMARY KEY (id);


--
-- TOC entry 2561 (class 2606 OID 1867323)
-- Name: discount_requests discount_requests_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.discount_requests
    ADD CONSTRAINT discount_requests_id_primary PRIMARY KEY (id);


--
-- TOC entry 2563 (class 2606 OID 1867325)
-- Name: discount_requests discount_requests_phone_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.discount_requests
    ADD CONSTRAINT discount_requests_phone_unique UNIQUE (phone, type);


--
-- TOC entry 2565 (class 2606 OID 1867327)
-- Name: districts districts_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.districts
    ADD CONSTRAINT districts_id_primary PRIMARY KEY (id);


--
-- TOC entry 2567 (class 2606 OID 1867329)
-- Name: error_logs error_logs_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.error_logs
    ADD CONSTRAINT error_logs_id_primary PRIMARY KEY (id);


--
-- TOC entry 2569 (class 2606 OID 1867331)
-- Name: event_guest_categories event_guest_category_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.event_guest_categories
    ADD CONSTRAINT event_guest_category_id_primary PRIMARY KEY (id);


--
-- TOC entry 2574 (class 2606 OID 1867333)
-- Name: events_guests events_guests_event_id_guest_phone; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events_guests
    ADD CONSTRAINT events_guests_event_id_guest_phone UNIQUE (event_id, guest_phone);


--
-- TOC entry 2576 (class 2606 OID 1867335)
-- Name: events_guests events_guests_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events_guests
    ADD CONSTRAINT events_guests_id_primary PRIMARY KEY (id);


--
-- TOC entry 2572 (class 2606 OID 1867337)
-- Name: events events_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events
    ADD CONSTRAINT events_id_primary PRIMARY KEY (id);


--
-- TOC entry 2578 (class 2606 OID 1867339)
-- Name: events_types events_types_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events_types
    ADD CONSTRAINT events_types_id_primary PRIMARY KEY (id);


--
-- TOC entry 2580 (class 2606 OID 1867341)
-- Name: events_types events_types_name; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events_types
    ADD CONSTRAINT events_types_name UNIQUE (name);


--
-- TOC entry 2657 (class 2606 OID 1867811)
-- Name: exhibitors exhibitors_event_id_business_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.exhibitors
    ADD CONSTRAINT exhibitors_event_id_business_id_unique UNIQUE (event_id, business_id);


--
-- TOC entry 2659 (class 2606 OID 1867809)
-- Name: exhibitors exhibitors_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.exhibitors
    ADD CONSTRAINT exhibitors_id_primary PRIMARY KEY (id);


--
-- TOC entry 2582 (class 2606 OID 1867343)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 2584 (class 2606 OID 1867345)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- TOC entry 2586 (class 2606 OID 1867347)
-- Name: file_albums file_album_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.file_albums
    ADD CONSTRAINT file_album_id_primary PRIMARY KEY (id);


--
-- TOC entry 2588 (class 2606 OID 1867349)
-- Name: files files_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.files
    ADD CONSTRAINT files_id_primary PRIMARY KEY (id);


--
-- TOC entry 2590 (class 2606 OID 1867351)
-- Name: live_attendees live_attendances_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.live_attendees
    ADD CONSTRAINT live_attendances_pkey PRIMARY KEY (id);


--
-- TOC entry 2594 (class 2606 OID 1867353)
-- Name: logs log_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.logs
    ADD CONSTRAINT log_id_primary PRIMARY KEY (id);


--
-- TOC entry 2592 (class 2606 OID 1867355)
-- Name: login_locations login_locations_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.login_locations
    ADD CONSTRAINT login_locations_id_primary PRIMARY KEY (id);


--
-- TOC entry 2596 (class 2606 OID 1867357)
-- Name: messages messages_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.messages
    ADD CONSTRAINT messages_id_primary PRIMARY KEY (id);


--
-- TOC entry 2598 (class 2606 OID 1867359)
-- Name: messages_sentby messages_sentby_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.messages_sentby
    ADD CONSTRAINT messages_sentby_id_primary PRIMARY KEY (id);


--
-- TOC entry 2600 (class 2606 OID 1867361)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 2661 (class 2606 OID 1867838)
-- Name: news news_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.news
    ADD CONSTRAINT news_id_primary PRIMARY KEY (id);


--
-- TOC entry 2602 (class 2606 OID 1867363)
-- Name: page_viewers page_viewers_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.page_viewers
    ADD CONSTRAINT page_viewers_id_primary PRIMARY KEY (id);


--
-- TOC entry 2605 (class 2606 OID 1867365)
-- Name: payments payments_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.payments
    ADD CONSTRAINT payments_id_primary PRIMARY KEY (id);


--
-- TOC entry 2607 (class 2606 OID 1867367)
-- Name: promotions promotion_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions
    ADD CONSTRAINT promotion_id_primary PRIMARY KEY (id);


--
-- TOC entry 2609 (class 2606 OID 1867369)
-- Name: promotions_payments promotions_payment_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_payments
    ADD CONSTRAINT promotions_payment_id_primary PRIMARY KEY (id);


--
-- TOC entry 2611 (class 2606 OID 1867371)
-- Name: promotions_reaches promotions_reaches_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_reaches
    ADD CONSTRAINT promotions_reaches_id_primary PRIMARY KEY (id);


--
-- TOC entry 2613 (class 2606 OID 1867373)
-- Name: regions regions_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.regions
    ADD CONSTRAINT regions_id_primary PRIMARY KEY (id);


--
-- TOC entry 2615 (class 2606 OID 1867375)
-- Name: reminders reminders_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.reminders
    ADD CONSTRAINT reminders_id_primary PRIMARY KEY (id);


--
-- TOC entry 2645 (class 2606 OID 1867724)
-- Name: schedules schedules_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.schedules
    ADD CONSTRAINT schedules_id_primary PRIMARY KEY (id);


--
-- TOC entry 2647 (class 2606 OID 1867726)
-- Name: schedules schedules_venue_id_activity_start_time_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.schedules
    ADD CONSTRAINT schedules_venue_id_activity_start_time_unique UNIQUE (venue_id, activity_start_time);


--
-- TOC entry 2617 (class 2606 OID 1867377)
-- Name: services services_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.services
    ADD CONSTRAINT services_id_primary PRIMARY KEY (id);


--
-- TOC entry 2619 (class 2606 OID 1867379)
-- Name: services services_name; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.services
    ADD CONSTRAINT services_name UNIQUE (name);


--
-- TOC entry 2649 (class 2606 OID 1867750)
-- Name: speakers speaker_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.speakers
    ADD CONSTRAINT speaker_id_primary PRIMARY KEY (id);


--
-- TOC entry 2651 (class 2606 OID 1867752)
-- Name: speakers speakers_user_id_event_id_schedule_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.speakers
    ADD CONSTRAINT speakers_user_id_event_id_schedule_id_unique UNIQUE (user_id, event_id, schedule_id);


--
-- TOC entry 2653 (class 2606 OID 1867779)
-- Name: sponsors sponsor_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.sponsors
    ADD CONSTRAINT sponsor_id_primary PRIMARY KEY (id);


--
-- TOC entry 2655 (class 2606 OID 1867781)
-- Name: sponsors sponsors_event_id_business_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.sponsors
    ADD CONSTRAINT sponsors_event_id_business_id_unique UNIQUE (event_id, business_id);


--
-- TOC entry 2621 (class 2606 OID 1867381)
-- Name: telegram_users telegram_users_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.telegram_users
    ADD CONSTRAINT telegram_users_id_primary PRIMARY KEY (id);


--
-- TOC entry 2623 (class 2606 OID 1867383)
-- Name: telegram_users telegram_users_telegram_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.telegram_users
    ADD CONSTRAINT telegram_users_telegram_id_unique UNIQUE (telegram_id);


--
-- TOC entry 2626 (class 2606 OID 1867385)
-- Name: users_keys user_keys_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_keys
    ADD CONSTRAINT user_keys_pkey PRIMARY KEY (id);


--
-- TOC entry 2628 (class 2606 OID 1867387)
-- Name: user_types user_types_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.user_types
    ADD CONSTRAINT user_types_pkey PRIMARY KEY (id);


--
-- TOC entry 2633 (class 2606 OID 1867389)
-- Name: users_events users_events_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_events
    ADD CONSTRAINT users_events_id_primary PRIMARY KEY (id);


--
-- TOC entry 2635 (class 2606 OID 1867391)
-- Name: users_events users_events_user_event_id_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_events
    ADD CONSTRAINT users_events_user_event_id_unique UNIQUE (user_id, event_id);


--
-- TOC entry 2631 (class 2606 OID 1867393)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 2641 (class 2606 OID 1867712)
-- Name: venues venues_body_unique; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.venues
    ADD CONSTRAINT venues_body_unique UNIQUE (body, cordinates);


--
-- TOC entry 2643 (class 2606 OID 1867710)
-- Name: venues venues_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.venues
    ADD CONSTRAINT venues_id_primary PRIMARY KEY (id);


--
-- TOC entry 2637 (class 2606 OID 1867395)
-- Name: wards wards_id_primary; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.wards
    ADD CONSTRAINT wards_id_primary PRIMARY KEY (id);


--
-- TOC entry 2639 (class 2606 OID 1867397)
-- Name: whatsapp_log whatsapp_log_pkey; Type: CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.whatsapp_log
    ADD CONSTRAINT whatsapp_log_pkey PRIMARY KEY (id);


--
-- TOC entry 2523 (class 1259 OID 1867398)
-- Name: fki_admin_bookings_package_id_foreign; Type: INDEX; Schema: dikodiko; Owner: postgres
--

CREATE INDEX fki_admin_bookings_package_id_foreign ON dikodiko.admin_bookings USING btree (admin_package_id);


--
-- TOC entry 2536 (class 1259 OID 1867399)
-- Name: fki_admin_payments_booking_id_foreign; Type: INDEX; Schema: dikodiko; Owner: postgres
--

CREATE INDEX fki_admin_payments_booking_id_foreign ON dikodiko.admin_payments USING btree (admin_booking_id);


--
-- TOC entry 2557 (class 1259 OID 1867400)
-- Name: fki_businesses_business_type_id_foreign; Type: INDEX; Schema: dikodiko; Owner: postgres
--

CREATE INDEX fki_businesses_business_type_id_foreign ON dikodiko.businesses USING btree (business_type_id);


--
-- TOC entry 2570 (class 1259 OID 1867401)
-- Name: fki_event_guest_category_event_id_foreign; Type: INDEX; Schema: dikodiko; Owner: postgres
--

CREATE INDEX fki_event_guest_category_event_id_foreign ON dikodiko.event_guest_categories USING btree (event_id);


--
-- TOC entry 2624 (class 1259 OID 1867402)
-- Name: fki_users_keys_user_id_foreign; Type: INDEX; Schema: dikodiko; Owner: postgres
--

CREATE INDEX fki_users_keys_user_id_foreign ON dikodiko.users_keys USING btree (user_id);


--
-- TOC entry 2629 (class 1259 OID 1867403)
-- Name: fki_users_user_type_id_foreign; Type: INDEX; Schema: dikodiko; Owner: postgres
--

CREATE INDEX fki_users_user_type_id_foreign ON dikodiko.users USING btree (user_type_id);


--
-- TOC entry 2603 (class 1259 OID 1867404)
-- Name: password_resets_email_index; Type: INDEX; Schema: dikodiko; Owner: postgres
--

CREATE INDEX password_resets_email_index ON dikodiko.password_resets USING btree (email);


--
-- TOC entry 2731 (class 2620 OID 1867405)
-- Name: messages count_sms_chars; Type: TRIGGER; Schema: dikodiko; Owner: postgres
--

CREATE TRIGGER count_sms_chars AFTER INSERT ON dikodiko.messages FOR EACH ROW EXECUTE PROCEDURE dikodiko.count_message_parts();


--
-- TOC entry 2666 (class 2606 OID 1867406)
-- Name: admin_bookings admin_bookings_package_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_bookings
    ADD CONSTRAINT admin_bookings_package_id_foreign FOREIGN KEY (admin_package_id) REFERENCES dikodiko.admin_packages(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2667 (class 2606 OID 1867411)
-- Name: admin_bookings admin_bookings_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_bookings
    ADD CONSTRAINT admin_bookings_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2668 (class 2606 OID 1867416)
-- Name: admin_feature_packages admin_feature_packages_admin_feature_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_feature_packages
    ADD CONSTRAINT admin_feature_packages_admin_feature_id_foreign FOREIGN KEY (admin_feature_id) REFERENCES dikodiko.admin_features(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2669 (class 2606 OID 1867421)
-- Name: admin_feature_packages admin_feature_packages_admin_package_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_feature_packages
    ADD CONSTRAINT admin_feature_packages_admin_package_id_foreign FOREIGN KEY (admin_package_id) REFERENCES dikodiko.admin_packages(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2670 (class 2606 OID 1867426)
-- Name: admin_integration_requests admin_integration_requests_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_integration_requests
    ADD CONSTRAINT admin_integration_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2671 (class 2606 OID 1867431)
-- Name: admin_packages_payments admin_packages_admin_package_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_packages_payments
    ADD CONSTRAINT admin_packages_admin_package_id_foreign FOREIGN KEY (admin_package_id) REFERENCES dikodiko.admin_packages(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2672 (class 2606 OID 1867436)
-- Name: admin_packages_payments admin_packages_payments_payment_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_packages_payments
    ADD CONSTRAINT admin_packages_payments_payment_id_foreign FOREIGN KEY (admin_payment_id) REFERENCES dikodiko.admin_payments(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2673 (class 2606 OID 1867441)
-- Name: admin_payments admin_payments_booking_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_payments
    ADD CONSTRAINT admin_payments_booking_id_foreign FOREIGN KEY (admin_booking_id) REFERENCES dikodiko.admin_bookings(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2674 (class 2606 OID 1867446)
-- Name: admin_payments admin_payments_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_payments
    ADD CONSTRAINT admin_payments_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2675 (class 2606 OID 1867451)
-- Name: admin_sms_brought admin_sms_brought_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_sms_brought
    ADD CONSTRAINT admin_sms_brought_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2676 (class 2606 OID 1867456)
-- Name: admin_supports admin_support_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.admin_supports
    ADD CONSTRAINT admin_support_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2679 (class 2606 OID 1867461)
-- Name: budgets budget_business_service_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budgets
    ADD CONSTRAINT budget_business_service_id_foreign FOREIGN KEY (business_service_id) REFERENCES dikodiko.business_services(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2677 (class 2606 OID 1867466)
-- Name: budget_payments budget_payments_created_by_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budget_payments
    ADD CONSTRAINT budget_payments_created_by_foreign FOREIGN KEY (created_by) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2680 (class 2606 OID 1867471)
-- Name: budgets budgets_users_events_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budgets
    ADD CONSTRAINT budgets_users_events_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2678 (class 2606 OID 1867476)
-- Name: budget_payments buget_payments_budget_id; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.budget_payments
    ADD CONSTRAINT buget_payments_budget_id FOREIGN KEY (budget_id) REFERENCES dikodiko.budgets(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2730 (class 2606 OID 1867923)
-- Name: business_ratings business_ratings_business_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_ratings
    ADD CONSTRAINT business_ratings_business_id_foreign FOREIGN KEY (business_id) REFERENCES dikodiko.businesses(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2728 (class 2606 OID 1867913)
-- Name: business_ratings business_ratings_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_ratings
    ADD CONSTRAINT business_ratings_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2729 (class 2606 OID 1867918)
-- Name: business_ratings business_ratings_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_ratings
    ADD CONSTRAINT business_ratings_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2681 (class 2606 OID 1867481)
-- Name: business_services businesses_business_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.business_services
    ADD CONSTRAINT businesses_business_id_foreign FOREIGN KEY (business_id) REFERENCES dikodiko.businesses(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2682 (class 2606 OID 1867486)
-- Name: businesses businesses_business_type_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.businesses
    ADD CONSTRAINT businesses_business_type_id_foreign FOREIGN KEY (business_type_id) REFERENCES dikodiko.business_types(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2683 (class 2606 OID 1867496)
-- Name: businesses businesses_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.businesses
    ADD CONSTRAINT businesses_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2684 (class 2606 OID 1867501)
-- Name: businesses businesses_ward_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.businesses
    ADD CONSTRAINT businesses_ward_id_foreign FOREIGN KEY (ward_id) REFERENCES dikodiko.wards(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2685 (class 2606 OID 1867506)
-- Name: discount_requests discount_requests_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.discount_requests
    ADD CONSTRAINT discount_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2686 (class 2606 OID 1867511)
-- Name: districts districts_region_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.districts
    ADD CONSTRAINT districts_region_id_foreign FOREIGN KEY (region_id) REFERENCES dikodiko.regions(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2687 (class 2606 OID 1867516)
-- Name: event_guest_categories event_guest_category_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.event_guest_categories
    ADD CONSTRAINT event_guest_category_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2688 (class 2606 OID 1867521)
-- Name: events events_district_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events
    ADD CONSTRAINT events_district_id_foreign FOREIGN KEY (district_id) REFERENCES dikodiko.districts(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2689 (class 2606 OID 1867526)
-- Name: events events_event_type_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events
    ADD CONSTRAINT events_event_type_id_foreign FOREIGN KEY (event_type_id) REFERENCES dikodiko.events_types(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2690 (class 2606 OID 1867531)
-- Name: events_guests events_guests_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.events_guests
    ADD CONSTRAINT events_guests_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2723 (class 2606 OID 1867817)
-- Name: exhibitors exhibitors_business_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.exhibitors
    ADD CONSTRAINT exhibitors_business_id_foreign FOREIGN KEY (business_id) REFERENCES dikodiko.businesses(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2722 (class 2606 OID 1867812)
-- Name: exhibitors exhibitors_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.exhibitors
    ADD CONSTRAINT exhibitors_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2724 (class 2606 OID 1867822)
-- Name: exhibitors exhibitors_venue_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.exhibitors
    ADD CONSTRAINT exhibitors_venue_id_foreign FOREIGN KEY (venue_id) REFERENCES dikodiko.venues(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2692 (class 2606 OID 1867930)
-- Name: file_albums file_album_business_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.file_albums
    ADD CONSTRAINT file_album_business_id_foreign FOREIGN KEY (business_id) REFERENCES dikodiko.businesses(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2691 (class 2606 OID 1867536)
-- Name: file_albums file_album_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.file_albums
    ADD CONSTRAINT file_album_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2693 (class 2606 OID 1867541)
-- Name: files file_file_album_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.files
    ADD CONSTRAINT file_file_album_id_foreign FOREIGN KEY (file_album_id) REFERENCES dikodiko.file_albums(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2694 (class 2606 OID 1867546)
-- Name: messages message_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.messages
    ADD CONSTRAINT message_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2695 (class 2606 OID 1867551)
-- Name: messages_sentby messages_sentby_message_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.messages_sentby
    ADD CONSTRAINT messages_sentby_message_id_foreign FOREIGN KEY (message_id) REFERENCES dikodiko.messages(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2727 (class 2606 OID 1867849)
-- Name: news news_business_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.news
    ADD CONSTRAINT news_business_id_foreign FOREIGN KEY (business_id) REFERENCES dikodiko.businesses(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2725 (class 2606 OID 1867839)
-- Name: news news_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.news
    ADD CONSTRAINT news_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2726 (class 2606 OID 1867844)
-- Name: news news_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.news
    ADD CONSTRAINT news_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2697 (class 2606 OID 1867861)
-- Name: page_viewers page_viewers_business_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.page_viewers
    ADD CONSTRAINT page_viewers_business_id_foreign FOREIGN KEY (business_id) REFERENCES dikodiko.businesses(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2696 (class 2606 OID 1867561)
-- Name: page_viewers page_viewers_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.page_viewers
    ADD CONSTRAINT page_viewers_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2698 (class 2606 OID 1867566)
-- Name: payments payments_events_guests_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.payments
    ADD CONSTRAINT payments_events_guests_id_foreign FOREIGN KEY (events_guests_id) REFERENCES dikodiko.events_guests(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2699 (class 2606 OID 1867571)
-- Name: promotions promotion_business_service_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions
    ADD CONSTRAINT promotion_business_service_id_foreign FOREIGN KEY (business_service_id) REFERENCES dikodiko.business_services(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2700 (class 2606 OID 1867576)
-- Name: promotions promotion_order_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions
    ADD CONSTRAINT promotion_order_id_foreign FOREIGN KEY (booking_id) REFERENCES dikodiko.admin_bookings(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2701 (class 2606 OID 1867581)
-- Name: promotions_payments promotion_payment_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_payments
    ADD CONSTRAINT promotion_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES dikodiko.promotions(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2702 (class 2606 OID 1867586)
-- Name: promotions_payments promotion_promotion_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_payments
    ADD CONSTRAINT promotion_promotion_id_foreign FOREIGN KEY (promotion_id) REFERENCES dikodiko.promotions(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2703 (class 2606 OID 1867591)
-- Name: promotions_reaches promotion_reaches_promotion_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_reaches
    ADD CONSTRAINT promotion_reaches_promotion_id_foreign FOREIGN KEY (promotion_id) REFERENCES dikodiko.promotions(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2704 (class 2606 OID 1867596)
-- Name: promotions_reaches promotion_reaches_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.promotions_reaches
    ADD CONSTRAINT promotion_reaches_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2705 (class 2606 OID 1867601)
-- Name: regions regions_country_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.regions
    ADD CONSTRAINT regions_country_id_foreign FOREIGN KEY (country_id) REFERENCES dikodiko.countries(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2706 (class 2606 OID 1867606)
-- Name: reminders reminders_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.reminders
    ADD CONSTRAINT reminders_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2713 (class 2606 OID 1867727)
-- Name: schedules schedules_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.schedules
    ADD CONSTRAINT schedules_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2714 (class 2606 OID 1867732)
-- Name: schedules schedules_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.schedules
    ADD CONSTRAINT schedules_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2715 (class 2606 OID 1867737)
-- Name: schedules schedules_venue_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.schedules
    ADD CONSTRAINT schedules_venue_id_foreign FOREIGN KEY (venue_id) REFERENCES dikodiko.venues(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2717 (class 2606 OID 1867758)
-- Name: speakers speakers_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.speakers
    ADD CONSTRAINT speakers_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2718 (class 2606 OID 1867763)
-- Name: speakers speakers_schedule_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.speakers
    ADD CONSTRAINT speakers_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES dikodiko.schedules(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2716 (class 2606 OID 1867753)
-- Name: speakers speakers_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.speakers
    ADD CONSTRAINT speakers_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2721 (class 2606 OID 1867792)
-- Name: sponsors sponsors_business_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.sponsors
    ADD CONSTRAINT sponsors_business_id_foreign FOREIGN KEY (business_id) REFERENCES dikodiko.businesses(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2719 (class 2606 OID 1867782)
-- Name: sponsors sponsors_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.sponsors
    ADD CONSTRAINT sponsors_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2720 (class 2606 OID 1867787)
-- Name: sponsors sponsors_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.sponsors
    ADD CONSTRAINT sponsors_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2707 (class 2606 OID 1867611)
-- Name: telegram_users telegram_users_event_guest_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.telegram_users
    ADD CONSTRAINT telegram_users_event_guest_id_foreign FOREIGN KEY (events_guest_id) REFERENCES dikodiko.events_guests(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2710 (class 2606 OID 1867616)
-- Name: users_events users_events_event_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_events
    ADD CONSTRAINT users_events_event_id_foreign FOREIGN KEY (event_id) REFERENCES dikodiko.events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2711 (class 2606 OID 1867621)
-- Name: users_events users_events_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_events
    ADD CONSTRAINT users_events_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2708 (class 2606 OID 1867626)
-- Name: users_keys users_keys_user_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users_keys
    ADD CONSTRAINT users_keys_user_id_foreign FOREIGN KEY (user_id) REFERENCES dikodiko.users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2709 (class 2606 OID 1867631)
-- Name: users users_user_type_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.users
    ADD CONSTRAINT users_user_type_id_foreign FOREIGN KEY (user_type_id) REFERENCES dikodiko.user_types(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2712 (class 2606 OID 1867636)
-- Name: wards wards_districs_id_foreign; Type: FK CONSTRAINT; Schema: dikodiko; Owner: postgres
--

ALTER TABLE ONLY dikodiko.wards
    ADD CONSTRAINT wards_districs_id_foreign FOREIGN KEY (district_id) REFERENCES dikodiko.districts(id) ON UPDATE CASCADE ON DELETE RESTRICT;


-- Completed on 2023-03-04 19:46:47

--
-- PostgreSQL database dump complete
--

