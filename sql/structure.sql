-- Table: password_reset_key
-- DROP TABLE password_reset_key;

CREATE TABLE password_reset_key
(
  id          BIGSERIAL                      NOT NULL,
  login_name  CHARACTER VARYING(255)         NOT NULL,
  key         CHARACTER VARYING(512)         NOT NULL,
  date_create TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT ('now' :: TEXT) :: TIMESTAMP(0) WITH TIME ZONE,
  usage_date  TIMESTAMP(0) WITHOUT TIME ZONE,
  is_used     INTEGER                        NOT NULL DEFAULT 0,
  is_expire   INTEGER                        NOT NULL DEFAULT 0,
  CONSTRAINT pk_passkey_id PRIMARY KEY (id)
)
WITH (
OIDS = FALSE
);

-- Table: session
-- DROP TABLE session;

CREATE TABLE session
(
  session_id   CHARACTER(32) NOT NULL,
  person_id    INTEGER       NOT NULL,
  session_data TEXT,
  remote_addr  INET          NOT NULL,
  id           BIGSERIAL     NOT NULL,
  date_create  TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT ('now' :: TEXT) :: TIMESTAMP(0) WITH TIME ZONE,
  last_update  TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT ('now' :: TEXT) :: TIMESTAMP(0) WITH TIME ZONE,
  CONSTRAINT pk_session_id PRIMARY KEY (session_id)
)
WITH (
OIDS = FALSE
);

-- Table: session_log
-- DROP TABLE session_log;

CREATE TABLE session_log
(
  id          BIGSERIAL                   NOT NULL,
  user_id     BIGINT,
  log_time    TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT now(),
  remote_addr TEXT,
  status      TEXT,
  CONSTRAINT pk_sess_log_id PRIMARY KEY (id)
)
WITH (
OIDS = FALSE
);

-- Table: unsuccess_login
-- DROP TABLE unsuccess_login;

CREATE TABLE unsuccess_login
(
  id          BIGSERIAL                   NOT NULL,
  user_name   TEXT,
  log_time    TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT now(),
  remote_addr TEXT,
  reason      TEXT,
  status      INTEGER,
  CONSTRAINT pk_un_log_id PRIMARY KEY (id)
)
WITH (
OIDS = FALSE
);

-- Table: user_table
-- DROP TABLE user_table;

CREATE TABLE user_table
(
  id               BIGSERIAL                      NOT NULL,
  login_name       CHARACTER VARYING(32)          NOT NULL,
  pass             CHARACTER VARYING(255),
  creation_date    TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT now(),
  first_name       CHARACTER VARYING(255),
  last_name        CHARACTER VARYING(255),
  rights           INTEGER                        NOT NULL DEFAULT 0,
  remote_addr      TEXT []                        NOT NULL DEFAULT '{*}' :: TEXT [],
  client_id        BIGINT,
  msisdn           BIGINT,
  email            CHARACTER VARYING(128),
  is_pass_expire   INTEGER                                 DEFAULT 1,
  pass_date_expire TIMESTAMP(0) WITHOUT TIME ZONE          DEFAULT now(),
  is_del           INTEGER                        NOT NULL DEFAULT 0,
  status           INTEGER                                 DEFAULT 1,
  default_language INTEGER                                 DEFAULT 1,
  CONSTRAINT pk_user_id PRIMARY KEY (id),
  CONSTRAINT uniq_login_name UNIQUE (login_name)
)
WITH (
OIDS = FALSE
);

-- Table: vcstats_module
-- DROP TABLE vcstats_module;

CREATE TABLE module
(
  id             BIGSERIAL              NOT NULL,
  names          CHARACTER VARYING(128) NOT NULL,
  uri_path CHARACTER VARYING(128) NOT NULL,
  remote_addr    TEXT [],
  positions      INTEGER,
  CONSTRAINT pk_module_id PRIMARY KEY (id),
  CONSTRAINT uniq_names UNIQUE (names),
  CONSTRAINT uniq_pos UNIQUE (positions)
)
WITH (
OIDS = FALSE
);

-- Table: "vcstats_rel_module<->user"
-- DROP TABLE "vcstats_rel_module<->user";

CREATE TABLE "rel_module<->user"
(
  id          BIGSERIAL                      NOT NULL,
  module_id   BIGINT                         NOT NULL,
  user_id     BIGINT                         NOT NULL,
  date_create TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT ('now' :: TEXT) :: TIMESTAMP(0) WITH TIME ZONE,
  CONSTRAINT "pk_module<->user" PRIMARY KEY (id),
  CONSTRAINT "uniq_module<->user" UNIQUE (module_id, user_id)
)
WITH (
OIDS = FALSE
);

-- Table: language
-- DROP TABLE language;

CREATE TABLE lang
(
  id            SERIAL                NOT NULL,
  languages     CHARACTER VARYING(32) NOT NULL,
  language_code CHARACTER VARYING(3)  NOT NULL,
  filename      CHARACTER VARYING(7)  NOT NULL,
  date_create   TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOW(),
  last_update   TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOW(),
  creator_id    BIGINT,
  updater_id    BIGINT,
  is_system     INTEGER,
  is_active     INTEGER               NOT NULL DEFAULT 0,
  language_flag CHARACTER VARYING(255),
  CONSTRAINT pk_language_id PRIMARY KEY (id)
)
WITH (
OIDS = FALSE
);

-- Insert queries
INSERT INTO user_table (id, login_name, pass, first_name, last_name, rights, msisdn) VALUES (1, 'Administrator', '', 'Admin', '', 4, '3598900');
INSERT INTO module (names, uri_path, remote_addr, positions) VALUES ('home', '/', '{*}', '999');
INSERT INTO module (names, uri_path, remote_addr, positions) VALUES ('users', 'users', '{*}', '1');
INSERT INTO module (names, uri_path, remote_addr, positions) VALUES ('translations', 'translations', '{*}', '3');

INSERT INTO "rel_module<->user" (module_id, user_id) (SELECT
                                                        id,
                                                        1
                                                      FROM module);

INSERT INTO lang (id, languages, language_code, filename, creator_id, is_system, is_active, language_flag)
VALUES (1, 'Bulgarian', 'BG', 'bg.ini', 1, 1, 1, 'bg.svg');

INSERT INTO lang (id, languages, language_code, filename, creator_id, is_system, is_active, language_flag)
VALUES (2, 'English', 'EN', 'en.ini', 1, 1, 1, 'en.svg');

-- You have to insert User manual because of password_hash in php