/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  swill
 * Created: May 15, 2021
 */

ALTER TABLE dikodiko.businesses
    ADD COLUMN status smallint;

COMMENT ON COLUMN dikodiko.businesses.status
    IS '1=self registration,0=registered by client';

    ALTER TABLE IF EXISTS dikodiko.events_types
    ADD COLUMN status smallint DEFAULT 0;

    CREATE TABLE IF NOT EXISTS dikodiko.otpcodes
(
    id integer NOT NULL DEFAULT nextval('dikodiko.otpcodes_id_seq'::regclass),
    phone character varying COLLATE pg_catalog."default",
    code character varying COLLATE pg_catalog."default",
    status smallint DEFAULT 0,
    uuid uuid DEFAULT dikodiko.uuid_generate_v4(),
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    CONSTRAINT otpcodes_pkey PRIMARY KEY (id)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS dikodiko.otpcodes
    OWNER to postgres;

    LTER TABLE IF EXISTS dikodiko.users
    ADD COLUMN verify_code character varying COLLATE pg_catalog."default";

    -- Table: dikodiko.political_parties

-- DROP TABLE IF EXISTS dikodiko.political_parties;

CREATE TABLE IF NOT EXISTS dikodiko.political_parties
(
    party_id integer NOT NULL DEFAULT nextval('dikodiko.political_parties_party_id_seq'::regclass),
    name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    abbreviation character varying(50) COLLATE pg_catalog."default",
    country_id integer NOT NULL,
    founded_year integer,
    ideology text COLLATE pg_catalog."default",
    headquarters character varying(255) COLLATE pg_catalog."default",
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    CONSTRAINT political_parties_pkey PRIMARY KEY (party_id),
    CONSTRAINT political_parties_country_id_fkey FOREIGN KEY (country_id)
        REFERENCES dikodiko.countries (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS dikodiko.political_parties
    OWNER to postgres;

    -- Table: dikodiko.users_political_parties

-- DROP TABLE IF EXISTS dikodiko.users_political_parties;

CREATE TABLE IF NOT EXISTS dikodiko.users_political_parties
(
    id integer NOT NULL DEFAULT nextval('dikodiko.users_political_parties_id_seq'::regclass),
    user_id integer NOT NULL,
    party_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    CONSTRAINT users_political_parties_pkey PRIMARY KEY (id),
    CONSTRAINT users_political_parties_party_id_fkey FOREIGN KEY (party_id)
        REFERENCES dikodiko.political_parties (party_id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT users_political_parties_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES dikodiko.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS dikodiko.users_political_parties
    OWNER to postgres;

    -- Set the schema
SET search_path TO dikodiko;

-- 1. Create the service_categories table
CREATE TABLE service_categories (
    category_id SERIAL PRIMARY KEY,
    category_name VARCHAR(255) UNIQUE NOT NULL
);

-- 2. Create the sub_categories table
CREATE TABLE sub_categories (
    sub_category_id SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL,
    sub_category_name VARCHAR(255) NOT NULL,
    registration_fee NUMERIC(10, 2) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES service_categories(category_id)
);

-- 3. Insert records into service_categories
INSERT INTO service_categories (category_name) VALUES
('Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'),
('Huduma za Kidigitali & Mawasiliano'),
('Vifaa na Logistics'),
('Matangazo na Uchapishaji'),
('Huduma za Watu');

-- 4. Insert records into sub_categories
-- For 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'
INSERT INTO sub_categories (category_id, sub_category_name, registration_fee) VALUES
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Wasanii (live bands, DJs, traditional dancers)', 500000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Watoa hotuba/MCs wa kampeni', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Photographers', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Campaign Manager', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Community Mobilizers', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Social media managers', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Political Intelligence Officers – wanaokuasanya taarifa kutoka kw', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Uongezaji Ufanisi wa Kampeni (Campaign Services)'), 'Accountant au Mhasibu wa Kampeni', 200000.00);

-- For 'Huduma za Kidigitali & Mawasiliano'
INSERT INTO sub_categories (category_id, sub_category_name, registration_fee) VALUES
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Kidigitali & Mawasiliano'), 'Social media marketing & management (Company)', 500000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Kidigitali & Mawasiliano'), 'Content creation (graphics, video editing, live streaming) Comp', 500000.00);

-- For 'Vifaa na Logistics'
INSERT INTO sub_categories (category_id, sub_category_name, registration_fee) VALUES
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Magari ya kampeni -Fuso', 250000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Magari ya kampeni -Kirikuu/Haice', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Power backup (generators, solar lights)', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Lighting systems (stage and field lights)', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Majukwaa', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Tents', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Chairs, tables', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Car Rental', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Bike Rental', 50000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Bajaji Rental on ground', 70000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Bajaji Cover Design', 100000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Vifaa na Logistics'), 'Mobile Public Toilets', 200000.00);

-- For 'Matangazo na Uchapishaji'
INSERT INTO sub_categories (category_id, sub_category_name, registration_fee) VALUES
((SELECT category_id FROM service_categories WHERE category_name = 'Matangazo na Uchapishaji'), 'Printing (banners, posters, flyers, T-shirts, kofia, vitenge)', 500000.00);

-- For 'Huduma za Watu'
INSERT INTO sub_categories (category_id, sub_category_name, registration_fee) VALUES
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Watu'), 'Catering', 200000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Watu'), 'Vijana wa ground (mobilizers)', 10000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Watu'), 'Maafisa wa ulinzi binafsii/bodyguards', 10000.00),
((SELECT category_id FROM service_categories WHERE category_name = 'Huduma za Watu'), 'Kampuni za Ulinzi', 500000.00);



-- ALTER TABLE IF EXISTS dikodiko.budgets
--     ADD CONSTRAINT budget_users_events_id_service_id_unique UNIQUE (event_id, business_service_id);