--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner:
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner:
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner:
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner:
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: archivestatus; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.archivestatus AS ENUM (
    'incomplete',
    'data-complete',
    'complete',
    'error'
);


ALTER TYPE public.archivestatus OWNER TO storiq;

--
-- Name: autocheckmode; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.autocheckmode AS ENUM (
    'quick mode',
    'thorough mode',
    'none'
);


ALTER TYPE public.autocheckmode OWNER TO storiq;

--
-- Name: changeraction; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.changeraction AS ENUM (
    'none',
    'put online',
    'put offline'
);


ALTER TYPE public.changeraction OWNER TO storiq;

--
-- Name: changerstatus; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.changerstatus AS ENUM (
    'error',
    'exporting',
    'go offline',
    'go online',
    'idle',
    'importing',
    'loading',
    'offline',
    'unknown',
    'unloading'
);


ALTER TYPE public.changerstatus OWNER TO storiq;

--
-- Name: drivestatus; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.drivestatus AS ENUM (
    'cleaning',
    'empty idle',
    'erasing',
    'error',
    'loaded idle',
    'loading',
    'positioning',
    'reading',
    'rewinding',
    'unknown',
    'unloading',
    'writing'
);


ALTER TYPE public.drivestatus OWNER TO storiq;

--
-- Name: filetype; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.filetype AS ENUM (
    'block device',
    'character device',
    'directory',
    'fifo',
    'regular file',
    'socket',
    'symbolic link'
);


ALTER TYPE public.filetype OWNER TO storiq;

--
-- Name: jobrecordnotif; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.jobrecordnotif AS ENUM (
    'normal',
    'important',
    'read'
);


ALTER TYPE public.jobrecordnotif OWNER TO storiq;

--
-- Name: jobrunstep; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.jobrunstep AS ENUM (
    'job',
    'on error',
    'pre job',
    'post job',
    'warm up'
);


ALTER TYPE public.jobrunstep OWNER TO storiq;

--
-- Name: jobstatus; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.jobstatus AS ENUM (
    'disable',
    'error',
    'finished',
    'pause',
    'running',
    'scheduled',
    'stopped',
    'unknown',
    'waiting'
);


ALTER TYPE public.jobstatus OWNER TO storiq;

--
-- Name: TYPE jobstatus; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON TYPE public.jobstatus IS 'disable => disabled,
error => error while running,
finished => task finished,
pause => waiting for user action,
running => running,
scheduled => not yet started or completed,
stopped => stopped by user,
waiting => waiting for a resource';


--
-- Name: loglevel; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.loglevel AS ENUM (
    'emergency',
    'alert',
    'critical',
    'error',
    'warning',
    'notice',
    'info',
    'debug'
);


ALTER TYPE public.loglevel OWNER TO storiq;

--
-- Name: mediaformatdatatype; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.mediaformatdatatype AS ENUM (
    'audio',
    'cleaning',
    'data',
    'video'
);


ALTER TYPE public.mediaformatdatatype OWNER TO storiq;

--
-- Name: mediaformatmode; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.mediaformatmode AS ENUM (
    'disk',
    'linear',
    'optical'
);


ALTER TYPE public.mediaformatmode OWNER TO storiq;

--
-- Name: mediastatus; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.mediastatus AS ENUM (
    'erasable',
    'error',
    'foreign',
    'in use',
    'locked',
    'needs replacement',
    'new',
    'pooled',
    'unknown'
);


ALTER TYPE public.mediastatus OWNER TO storiq;

--
-- Name: mediatype; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.mediatype AS ENUM (
    'cleaning',
    'rewritable',
    'worm'
);


ALTER TYPE public.mediatype OWNER TO storiq;

--
-- Name: metatype; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.metatype AS ENUM (
    'archive',
    'archivefile',
    'archivevolume',
    'host',
    'media',
    'pool',
    'report',
    'users',
    'vtl'
);


ALTER TYPE public.metatype OWNER TO storiq;

--
-- Name: proxystatus; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.proxystatus AS ENUM (
    'todo',
    'running',
    'done',
    'error'
);


ALTER TYPE public.proxystatus OWNER TO storiq;

--
-- Name: scripttype; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.scripttype AS ENUM (
    'on error',
    'pre job',
    'post job'
);


ALTER TYPE public.scripttype OWNER TO storiq;

--
-- Name: unbreakablelevel; Type: TYPE; Schema: public; Owner: storiq
--

CREATE TYPE public.unbreakablelevel AS ENUM (
    'archive',
    'file',
    'none'
);


ALTER TYPE public.unbreakablelevel OWNER TO storiq;

--
-- Name: check_metadata(); Type: FUNCTION; Schema: public; Owner: storiq
--

CREATE FUNCTION public.check_metadata() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        IF TG_OP = 'UPDATE' AND OLD.id != NEW.id THEN
            UPDATE Metadata SET id = NEW.id WHERE id = OLD.id AND type = TG_TABLE_NAME::MetaType;
        ELSIF TG_OP = 'DELETE' THEN
            DELETE FROM Metadata WHERE id = OLD.id AND type = TG_TABLE_NAME::MetaType;
        END IF;
        RETURN NEW;
    END;
$$;


ALTER FUNCTION public.check_metadata() OWNER TO storiq;

--
-- Name: json_object_set_key(json, text, anyelement); Type: FUNCTION; Schema: public; Owner: storiq
--

CREATE FUNCTION public.json_object_set_key(json json, key_to_set text, value_to_set anyelement) RETURNS json
    LANGUAGE sql IMMUTABLE STRICT
    AS $$
SELECT CONCAT('{', STRING_AGG(TO_JSON("key") || ':' || "value", ','), '}')::JSON
    FROM (
        SELECT *
        FROM JSON_EACH("json")
        WHERE "key" <> "key_to_set"
        UNION ALL
        SELECT "key_to_set", TO_JSON("value_to_set")) AS "fields"
$$;


ALTER FUNCTION public.json_object_set_key(json json, key_to_set text, value_to_set anyelement) OWNER TO storiq;

--
-- Name: log_metadata(); Type: FUNCTION; Schema: public; Owner: storiq
--

CREATE FUNCTION public.log_metadata() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        IF TG_OP = 'UPDATE' AND OLD.type != NEW.type THEN
            RAISE EXCEPTION 'type of metadata should not be modified' USING ERRCODE = '09000';
        ELSIF TG_OP = 'DELETE' THEN
            INSERT INTO MetadataLog(id, type, key, value, login, updated)
                VALUES (OLD.id, OLD.type, OLD.key, OLD.value, OLD.login, FALSE);
            RETURN OLD;
        ELSIF OLD != NEW THEN
            INSERT INTO MetadataLog(id, type, key, value, login, updated)
                VALUES (OLD.id, OLD.type, OLD.key, OLD.value, OLD.login, TRUE);
            RETURN NEW;
        ELSE
            RETURN NEW;
        END IF;
    END;
$$;


ALTER FUNCTION public.log_metadata() OWNER TO storiq;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: application; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.application (
    id integer NOT NULL,
    name text NOT NULL,
    apikey uuid
);


ALTER TABLE public.application OWNER TO storiq;

--
-- Name: application_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.application_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.application_id_seq OWNER TO storiq;

--
-- Name: application_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.application_id_seq OWNED BY public.application.id;


--
-- Name: archive; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archive (
    id bigint NOT NULL,
    uuid uuid NOT NULL,
    name text NOT NULL,
    creator integer NOT NULL,
    owner integer NOT NULL,
    canappend boolean DEFAULT true NOT NULL,
    status public.archivestatus NOT NULL,
    pool integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
);


ALTER TABLE public.archive OWNER TO storiq;

--
-- Name: archive_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.archive_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.archive_id_seq OWNER TO storiq;

--
-- Name: archive_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.archive_id_seq OWNED BY public.archive.id;


--
-- Name: archivefile; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archivefile (
    id bigint NOT NULL,
    name text NOT NULL,
    type public.filetype NOT NULL,
    mimetype character varying(255) NOT NULL,
    ownerid integer DEFAULT 0 NOT NULL,
    owner character varying(255) NOT NULL,
    groupid integer DEFAULT 0 NOT NULL,
    groups character varying(255) NOT NULL,
    perm smallint NOT NULL,
    ctime timestamp(3) with time zone NOT NULL,
    mtime timestamp(3) with time zone NOT NULL,
    size bigint NOT NULL,
    parent bigint NOT NULL,
    CONSTRAINT archivefile_perm_check CHECK ((perm >= 0)),
    CONSTRAINT archivefile_size_check CHECK ((size >= 0))
);


ALTER TABLE public.archivefile OWNER TO storiq;

--
-- Name: archivefile_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.archivefile_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.archivefile_id_seq OWNER TO storiq;

--
-- Name: archivefile_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.archivefile_id_seq OWNED BY public.archivefile.id;


--
-- Name: archivefiletoarchivevolume; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archivefiletoarchivevolume (
    archivevolume bigint NOT NULL,
    archivefile bigint NOT NULL,
    index bigint NOT NULL,
    blocknumber bigint NOT NULL,
    archivetime timestamp(3) with time zone NOT NULL,
    checktime timestamp(3) with time zone,
    checksumok boolean DEFAULT false NOT NULL,
    versions int4range NOT NULL,
    alternatepath text,
    CONSTRAINT archivefiletoarchivevolume_blocknumber_check CHECK ((blocknumber >= 0)),
    CONSTRAINT archivefiletoarchivevolume_index_check CHECK ((index >= 0))
);


ALTER TABLE public.archivefiletoarchivevolume OWNER TO storiq;

--
-- Name: archivefiletochecksumresult; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archivefiletochecksumresult (
    archivefile bigint NOT NULL,
    checksumresult bigint NOT NULL
);


ALTER TABLE public.archivefiletochecksumresult OWNER TO storiq;

--
-- Name: archiveformat; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archiveformat (
    id integer NOT NULL,
    name character varying(32) NOT NULL,
    readable boolean NOT NULL,
    writable boolean NOT NULL
);


ALTER TABLE public.archiveformat OWNER TO storiq;

--
-- Name: archiveformat_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.archiveformat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.archiveformat_id_seq OWNER TO storiq;

--
-- Name: archiveformat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.archiveformat_id_seq OWNED BY public.archiveformat.id;


--
-- Name: archivemirror; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archivemirror (
    id integer NOT NULL,
    poolmirror integer
);


ALTER TABLE public.archivemirror OWNER TO storiq;

--
-- Name: archivemirror_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.archivemirror_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.archivemirror_id_seq OWNER TO storiq;

--
-- Name: archivemirror_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.archivemirror_id_seq OWNED BY public.archivemirror.id;


--
-- Name: archivetoarchivemirror; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archivetoarchivemirror (
    archive bigint NOT NULL,
    archivemirror integer NOT NULL,
    lastupdate timestamp(3) with time zone DEFAULT now() NOT NULL,
    jobrun bigint NOT NULL
);


ALTER TABLE public.archivetoarchivemirror OWNER TO storiq;

--
-- Name: archivevolume; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archivevolume (
    id bigint NOT NULL,
    sequence integer DEFAULT 0 NOT NULL,
    size bigint DEFAULT 0 NOT NULL,
    starttime timestamp(3) with time zone NOT NULL,
    endtime timestamp(3) with time zone,
    checktime timestamp(3) with time zone,
    checksumok boolean DEFAULT false NOT NULL,
    archive bigint NOT NULL,
    media integer NOT NULL,
    mediaposition integer DEFAULT 0 NOT NULL,
    jobrun bigint,
    purged bigint,
    versions int4range NOT NULL,
    CONSTRAINT archivevolume_mediaposition_check CHECK ((mediaposition >= 0)),
    CONSTRAINT archivevolume_sequence_check CHECK ((sequence >= 0)),
    CONSTRAINT archivevolume_size_check CHECK ((size >= 0)),
    CONSTRAINT archivevolume_time CHECK ((starttime <= endtime))
);


ALTER TABLE public.archivevolume OWNER TO storiq;

--
-- Name: COLUMN archivevolume.starttime; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.archivevolume.starttime IS 'Start time of archive volume creation';


--
-- Name: COLUMN archivevolume.endtime; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.archivevolume.endtime IS 'End time of archive volume creation';


--
-- Name: COLUMN archivevolume.checktime; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.archivevolume.checktime IS 'Last time of checked time';


--
-- Name: archivevolume_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.archivevolume_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.archivevolume_id_seq OWNER TO storiq;

--
-- Name: archivevolume_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.archivevolume_id_seq OWNED BY public.archivevolume.id;


--
-- Name: archivevolumetochecksumresult; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.archivevolumetochecksumresult (
    archivevolume bigint NOT NULL,
    checksumresult bigint NOT NULL
);


ALTER TABLE public.archivevolumetochecksumresult OWNER TO storiq;

--
-- Name: backup; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.backup (
    id bigint NOT NULL,
    "timestamp" timestamp(3) with time zone DEFAULT now() NOT NULL,
    nbmedia integer DEFAULT 0 NOT NULL,
    nbarchive integer DEFAULT 0 NOT NULL,
    jobrun bigint NOT NULL,
    CONSTRAINT backup_nbarchive_check CHECK ((nbarchive >= 0)),
    CONSTRAINT backup_nbmedia_check CHECK ((nbmedia >= 0))
);


ALTER TABLE public.backup OWNER TO storiq;

--
-- Name: backup_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.backup_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.backup_id_seq OWNER TO storiq;

--
-- Name: backup_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.backup_id_seq OWNED BY public.backup.id;


--
-- Name: backupvolume; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.backupvolume (
    id bigint NOT NULL,
    sequence integer DEFAULT 0 NOT NULL,
    size bigint DEFAULT 0 NOT NULL,
    media integer NOT NULL,
    mediaposition integer DEFAULT 0 NOT NULL,
    checktime timestamp(3) with time zone,
    checksumok boolean DEFAULT false NOT NULL,
    backup bigint NOT NULL,
    CONSTRAINT backupvolume_mediaposition_check CHECK ((mediaposition >= 0)),
    CONSTRAINT backupvolume_sequence_check CHECK ((sequence >= 0)),
    CONSTRAINT backupvolume_size_check CHECK ((size >= 0))
);


ALTER TABLE public.backupvolume OWNER TO storiq;

--
-- Name: backupvolume_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.backupvolume_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.backupvolume_id_seq OWNER TO storiq;

--
-- Name: backupvolume_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.backupvolume_id_seq OWNED BY public.backupvolume.id;


--
-- Name: backupvolumetochecksumresult; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.backupvolumetochecksumresult (
    backupvolume bigint NOT NULL,
    checksumresult bigint NOT NULL
);


ALTER TABLE public.backupvolumetochecksumresult OWNER TO storiq;

--
-- Name: changer; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.changer (
    id integer NOT NULL,
    model character varying(64) NOT NULL,
    vendor character varying(64) NOT NULL,
    firmwarerev character varying(64) NOT NULL,
    serialnumber character varying(64) NOT NULL,
    wwn character varying(64),
    barcode boolean NOT NULL,
    status public.changerstatus NOT NULL,
    isonline boolean DEFAULT true NOT NULL,
    action public.changeraction DEFAULT 'none'::public.changeraction NOT NULL,
    enable boolean DEFAULT true NOT NULL,
    host integer NOT NULL
);


ALTER TABLE public.changer OWNER TO storiq;

--
-- Name: changer_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.changer_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.changer_id_seq OWNER TO storiq;

--
-- Name: changer_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.changer_id_seq OWNED BY public.changer.id;


--
-- Name: changerslot; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.changerslot (
    changer integer NOT NULL,
    index integer NOT NULL,
    drive integer,
    media integer,
    isieport boolean DEFAULT false NOT NULL,
    enable boolean DEFAULT true NOT NULL
);


ALTER TABLE public.changerslot OWNER TO storiq;

--
-- Name: checksum; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.checksum (
    id integer NOT NULL,
    name character varying(64) NOT NULL,
    deflt boolean DEFAULT false NOT NULL
);


ALTER TABLE public.checksum OWNER TO storiq;

--
-- Name: TABLE checksum; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON TABLE public.checksum IS 'Contains only checksum available';


--
-- Name: checksum_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.checksum_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.checksum_id_seq OWNER TO storiq;

--
-- Name: checksum_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.checksum_id_seq OWNED BY public.checksum.id;


--
-- Name: checksumresult; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.checksumresult (
    id bigint NOT NULL,
    checksum integer NOT NULL,
    result text NOT NULL
);


ALTER TABLE public.checksumresult OWNER TO storiq;

--
-- Name: checksumresult_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.checksumresult_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.checksumresult_id_seq OWNER TO storiq;

--
-- Name: checksumresult_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.checksumresult_id_seq OWNED BY public.checksumresult.id;


--
-- Name: drive; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.drive (
    id integer NOT NULL,
    model character varying(64) NOT NULL,
    vendor character varying(64) NOT NULL,
    firmwarerev character varying(64) NOT NULL,
    serialnumber character varying(64) NOT NULL,
    status public.drivestatus NOT NULL,
    operationduration real DEFAULT 0 NOT NULL,
    lastclean timestamp(3) with time zone,
    enable boolean DEFAULT true NOT NULL,
    changer integer,
    driveformat integer,
    CONSTRAINT drive_operationduration_check CHECK ((operationduration >= (0)::double precision))
);


ALTER TABLE public.drive OWNER TO storiq;

--
-- Name: drive_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.drive_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.drive_id_seq OWNER TO storiq;

--
-- Name: drive_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.drive_id_seq OWNED BY public.drive.id;


--
-- Name: driveformat; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.driveformat (
    id integer NOT NULL,
    name character varying(64) NOT NULL,
    densitycode smallint NOT NULL,
    mode public.mediaformatmode NOT NULL,
    cleaninginterval interval NOT NULL,
    CONSTRAINT driveformat_cleaninginterval_check CHECK ((cleaninginterval >= '7 days'::interval)),
    CONSTRAINT driveformat_densitycode_check CHECK ((densitycode > 0))
);


ALTER TABLE public.driveformat OWNER TO storiq;

--
-- Name: COLUMN driveformat.cleaninginterval; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.driveformat.cleaninginterval IS 'Interval between two cleaning in days';


--
-- Name: driveformat_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.driveformat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.driveformat_id_seq OWNER TO storiq;

--
-- Name: driveformat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.driveformat_id_seq OWNED BY public.driveformat.id;


--
-- Name: driveformatsupport; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.driveformatsupport (
    driveformat integer NOT NULL,
    mediaformat integer NOT NULL,
    read boolean DEFAULT true NOT NULL,
    write boolean DEFAULT true NOT NULL
);


ALTER TABLE public.driveformatsupport OWNER TO storiq;

--
-- Name: host; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.host (
    id integer NOT NULL,
    uuid uuid NOT NULL,
    name character varying(255) NOT NULL,
    domaine character varying(255),
    description text,
    daemonversion text NOT NULL,
    updated timestamp(3) with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.host OWNER TO storiq;

--
-- Name: host_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.host_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.host_id_seq OWNER TO storiq;

--
-- Name: host_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.host_id_seq OWNED BY public.host.id;


--
-- Name: job; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.job (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type integer NOT NULL,
    nextstart timestamp(3) with time zone DEFAULT now() NOT NULL,
    "interval" interval,
    repetition integer DEFAULT 1 NOT NULL,
    status public.jobstatus DEFAULT 'scheduled'::public.jobstatus NOT NULL,
    update timestamp(3) with time zone DEFAULT now() NOT NULL,
    archive bigint,
    backup bigint,
    media integer,
    pool integer,
    host integer NOT NULL,
    login integer NOT NULL,
    metadata json DEFAULT '{}'::json NOT NULL,
    options json DEFAULT '{}'::json NOT NULL
);


ALTER TABLE public.job OWNER TO storiq;

--
-- Name: job_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.job_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.job_id_seq OWNER TO storiq;

--
-- Name: job_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.job_id_seq OWNED BY public.job.id;


--
-- Name: jobrecord; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.jobrecord (
    id bigint NOT NULL,
    jobrun bigint NOT NULL,
    "timestamp" timestamp(3) with time zone DEFAULT now() NOT NULL,
    status public.jobstatus NOT NULL,
    level public.loglevel NOT NULL,
    message text NOT NULL,
    notif public.jobrecordnotif DEFAULT 'normal'::public.jobrecordnotif NOT NULL,
    CONSTRAINT jobrecord_status_check CHECK ((status <> 'disable'::public.jobstatus))
);


ALTER TABLE public.jobrecord OWNER TO storiq;

--
-- Name: jobrecord_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.jobrecord_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobrecord_id_seq OWNER TO storiq;

--
-- Name: jobrecord_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.jobrecord_id_seq OWNED BY public.jobrecord.id;


--
-- Name: jobrun; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.jobrun (
    id bigint NOT NULL,
    job bigint NOT NULL,
    numrun integer DEFAULT 1 NOT NULL,
    starttime timestamp(3) with time zone DEFAULT now() NOT NULL,
    endtime timestamp(3) with time zone,
    status public.jobstatus DEFAULT 'running'::public.jobstatus NOT NULL,
    step public.jobrunstep DEFAULT 'pre job'::public.jobrunstep NOT NULL,
    done double precision DEFAULT 0 NOT NULL,
    exitcode integer DEFAULT 0 NOT NULL,
    stoppedbyuser boolean DEFAULT false NOT NULL,
    CONSTRAINT jobrun_check CHECK ((starttime <= endtime)),
    CONSTRAINT jobrun_numrun_check CHECK ((numrun > 0))
);


ALTER TABLE public.jobrun OWNER TO storiq;

--
-- Name: jobrun_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.jobrun_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobrun_id_seq OWNER TO storiq;

--
-- Name: jobrun_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.jobrun_id_seq OWNED BY public.jobrun.id;


--
-- Name: jobtoselectedfile; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.jobtoselectedfile (
    job bigint NOT NULL,
    selectedfile bigint NOT NULL
);


ALTER TABLE public.jobtoselectedfile OWNER TO storiq;

--
-- Name: jobtype; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.jobtype (
    id integer NOT NULL,
    name character varying(255) NOT NULL
);


ALTER TABLE public.jobtype OWNER TO storiq;

--
-- Name: jobtype_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.jobtype_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobtype_id_seq OWNER TO storiq;

--
-- Name: jobtype_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.jobtype_id_seq OWNED BY public.jobtype.id;


--
-- Name: log; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.log (
    id bigint NOT NULL,
    application integer NOT NULL,
    level public.loglevel NOT NULL,
    "time" timestamp(3) with time zone NOT NULL,
    message text NOT NULL,
    host integer NOT NULL,
    login integer
);


ALTER TABLE public.log OWNER TO storiq;

--
-- Name: log_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.log_id_seq OWNER TO storiq;

--
-- Name: log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.log_id_seq OWNED BY public.log.id;


--
-- Name: media; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.media (
    id integer NOT NULL,
    uuid uuid,
    label character varying(64),
    mediumserialnumber character varying(36),
    name character varying(255),
    status public.mediastatus NOT NULL,
    firstused timestamp(3) with time zone NOT NULL,
    usebefore timestamp(3) with time zone NOT NULL,
    lastread timestamp(3) with time zone,
    lastwrite timestamp(3) with time zone,
    loadcount integer DEFAULT 0 NOT NULL,
    readcount integer DEFAULT 0 NOT NULL,
    writecount integer DEFAULT 0 NOT NULL,
    operationcount integer DEFAULT 0 NOT NULL,
    nbtotalblockread bigint DEFAULT 0 NOT NULL,
    nbtotalblockwrite bigint DEFAULT 0 NOT NULL,
    nbreaderror integer DEFAULT 0 NOT NULL,
    nbwriteerror integer DEFAULT 0 NOT NULL,
    nbfiles integer DEFAULT 0 NOT NULL,
    blocksize integer DEFAULT 0 NOT NULL,
    freeblock bigint NOT NULL,
    totalblock bigint NOT NULL,
    haspartition boolean DEFAULT false NOT NULL,
    append boolean DEFAULT true NOT NULL,
    type public.mediatype DEFAULT 'rewritable'::public.mediatype NOT NULL,
    writelock boolean DEFAULT false NOT NULL,
    archiveformat integer,
    mediaformat integer NOT NULL,
    pool integer,
    CONSTRAINT media_blocksize_check CHECK ((blocksize >= 0)),
    CONSTRAINT media_check CHECK ((firstused < usebefore)),
    CONSTRAINT media_freeblock_check CHECK ((freeblock >= 0)),
    CONSTRAINT media_loadcount_check CHECK ((loadcount >= 0)),
    CONSTRAINT media_nbfiles_check CHECK ((nbfiles >= 0)),
    CONSTRAINT media_nbreaderror_check CHECK ((nbreaderror >= 0)),
    CONSTRAINT media_nbtotalblockread_check CHECK ((nbtotalblockread >= 0)),
    CONSTRAINT media_nbtotalblockwrite_check CHECK ((nbtotalblockwrite >= 0)),
    CONSTRAINT media_nbwriteerror_check CHECK ((nbwriteerror >= 0)),
    CONSTRAINT media_operationcount_check CHECK ((operationcount >= 0)),
    CONSTRAINT media_readcount_check CHECK ((readcount >= 0)),
    CONSTRAINT media_totalblock_check CHECK ((totalblock >= 0)),
    CONSTRAINT media_writecount_check CHECK ((writecount >= 0))
);


ALTER TABLE public.media OWNER TO storiq;

--
-- Name: COLUMN media.label; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.media.label IS 'Contains an UUID';


--
-- Name: COLUMN media.append; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.media.append IS 'Can add file into this media';


--
-- Name: COLUMN media.writelock; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.media.writelock IS 'Media is write protected';


--
-- Name: media_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.media_id_seq OWNER TO storiq;

--
-- Name: media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.media_id_seq OWNED BY public.media.id;


--
-- Name: mediaformat; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.mediaformat (
    id integer NOT NULL,
    name character varying(64) NOT NULL,
    datatype public.mediaformatdatatype NOT NULL,
    mode public.mediaformatmode NOT NULL,
    maxloadcount integer NOT NULL,
    maxreadcount integer NOT NULL,
    maxwritecount integer NOT NULL,
    maxopcount integer NOT NULL,
    lifespan interval NOT NULL,
    capacity bigint NOT NULL,
    blocksize integer DEFAULT 0 NOT NULL,
    densitycode smallint NOT NULL,
    supportpartition boolean DEFAULT false NOT NULL,
    supportmam boolean DEFAULT false NOT NULL,
    CONSTRAINT mediaformat_blocksize_check CHECK ((blocksize >= 0)),
    CONSTRAINT mediaformat_capacity_check CHECK ((capacity > 0)),
    CONSTRAINT mediaformat_lifespan_check CHECK ((lifespan > '1 year'::interval)),
    CONSTRAINT mediaformat_maxloadcount_check CHECK ((maxloadcount > 0)),
    CONSTRAINT mediaformat_maxopcount_check CHECK ((maxopcount > 0)),
    CONSTRAINT mediaformat_maxreadcount_check CHECK ((maxreadcount > 0)),
    CONSTRAINT mediaformat_maxwritecount_check CHECK ((maxwritecount > 0))
);


ALTER TABLE public.mediaformat OWNER TO storiq;

--
-- Name: COLUMN mediaformat.blocksize; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.mediaformat.blocksize IS 'Default block size';


--
-- Name: COLUMN mediaformat.supportpartition; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.mediaformat.supportpartition IS 'Is the media can be partitionned';


--
-- Name: COLUMN mediaformat.supportmam; Type: COMMENT; Schema: public; Owner: storiq
--

COMMENT ON COLUMN public.mediaformat.supportmam IS 'MAM: Medium Axiliary Memory, contains some usefull data';


--
-- Name: mediaformat_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.mediaformat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.mediaformat_id_seq OWNER TO storiq;

--
-- Name: mediaformat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.mediaformat_id_seq OWNED BY public.mediaformat.id;


--
-- Name: medialabel; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.medialabel (
    id bigint NOT NULL,
    name text NOT NULL,
    media integer NOT NULL
);


ALTER TABLE public.medialabel OWNER TO storiq;

--
-- Name: medialabel_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.medialabel_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.medialabel_id_seq OWNER TO storiq;

--
-- Name: medialabel_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.medialabel_id_seq OWNED BY public.medialabel.id;


--
-- Name: metadata; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.metadata (
    id bigint NOT NULL,
    type public.metatype NOT NULL,
    key text NOT NULL,
    value jsonb NOT NULL,
    login integer NOT NULL
);


ALTER TABLE public.metadata OWNER TO storiq;

--
-- Name: metadatalog; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.metadatalog (
    id bigint NOT NULL,
    type public.metatype NOT NULL,
    key text NOT NULL,
    value jsonb NOT NULL,
    login integer NOT NULL,
    "timestamp" timestamp(3) with time zone DEFAULT now() NOT NULL,
    updated boolean NOT NULL
);


ALTER TABLE public.metadatalog OWNER TO storiq;

--
-- Name: pool; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.pool (
    id integer NOT NULL,
    uuid uuid NOT NULL,
    name character varying(64) NOT NULL,
    archiveformat integer NOT NULL,
    mediaformat integer NOT NULL,
    autocheck public.autocheckmode DEFAULT 'none'::public.autocheckmode NOT NULL,
    lockcheck boolean DEFAULT false NOT NULL,
    growable boolean DEFAULT false NOT NULL,
    unbreakablelevel public.unbreakablelevel DEFAULT 'none'::public.unbreakablelevel NOT NULL,
    rewritable boolean DEFAULT true NOT NULL,
    metadata json DEFAULT '{}'::json NOT NULL,
    backuppool boolean DEFAULT false NOT NULL,
    pooloriginal integer,
    poolmirror integer,
    deleted boolean DEFAULT false NOT NULL
);


ALTER TABLE public.pool OWNER TO storiq;

--
-- Name: selectedfile; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.selectedfile (
    id bigint NOT NULL,
    path text NOT NULL
);


ALTER TABLE public.selectedfile OWNER TO storiq;

--
-- Name: milestones_files; Type: MATERIALIZED VIEW; Schema: public; Owner: storiq
--

CREATE MATERIALIZED VIEW public.milestones_files AS
 SELECT sa.id AS archive,
    sa.archive_name,
    sa.archive_size,
    sa.starttime AS archive_starttime,
    sa.endtime AS archive_endtime,
    sa.archive_versions,
    af.id AS archivefile,
    af.name,
    "substring"(af.name, (char_length("substring"(af.name, '(.+/)[^/]+'::text)) + 1)) AS file_name,
    af.type,
    af.mimetype,
    af.ctime AS file_ctime,
    af.mtime AS file_mtime,
    af.size AS file_size,
    af.owner AS file_owner,
    saf.versions AS file_versions,
    (af.name = sf.path) AS file_isroot,
    (saf.medias)::text AS medias,
    saf.medias_length,
    sp.id AS pool,
    sp.name AS pool_name
   FROM ((((public.archivefile af
     JOIN ( SELECT afv.archivefile,
            av.archive,
            json_agg((COALESCE(m.name, m.label, m.mediumserialnumber))::text) AS medias,
            max(char_length((COALESCE(m.name, m.label, m.mediumserialnumber))::text)) AS medias_length,
            m.pool,
            int4range(min(lower(afv.versions)), max(upper(afv.versions))) AS versions
           FROM ((public.archivefiletoarchivevolume afv
             JOIN public.archivevolume av ON ((afv.archivevolume = av.id)))
             JOIN public.media m ON ((av.media = m.id)))
          GROUP BY afv.archivefile, av.archive, m.pool) saf ON ((af.id = saf.archivefile)))
     JOIN ( SELECT a.id,
            a.name AS archive_name,
            sum(av.size) AS archive_size,
            min(av.starttime) AS starttime,
            max(av.endtime) AS endtime,
            int4range(min(lower(av.versions)), max(upper(av.versions))) AS archive_versions
           FROM (public.archive a
             JOIN public.archivevolume av ON ((a.id = av.archive)))
          GROUP BY a.id, a.name) sa ON ((saf.archive = sa.id)))
     JOIN public.pool sp ON ((saf.pool = sp.id)))
     JOIN public.selectedfile sf ON ((af.parent = sf.id)))
  WITH NO DATA;


ALTER TABLE public.milestones_files OWNER TO storiq;

--
-- Name: pool_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.pool_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pool_id_seq OWNER TO storiq;

--
-- Name: pool_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.pool_id_seq OWNED BY public.pool.id;


--
-- Name: poolgroup; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.poolgroup (
    id integer NOT NULL,
    uuid uuid NOT NULL,
    name character varying(64) NOT NULL
);


ALTER TABLE public.poolgroup OWNER TO storiq;

--
-- Name: poolgroup_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.poolgroup_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.poolgroup_id_seq OWNER TO storiq;

--
-- Name: poolgroup_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.poolgroup_id_seq OWNED BY public.poolgroup.id;


--
-- Name: poolmirror; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.poolmirror (
    id integer NOT NULL,
    uuid uuid NOT NULL,
    name character varying(64) NOT NULL,
    synchronized boolean NOT NULL
);


ALTER TABLE public.poolmirror OWNER TO storiq;

--
-- Name: poolmirror_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.poolmirror_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.poolmirror_id_seq OWNER TO storiq;

--
-- Name: poolmirror_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.poolmirror_id_seq OWNED BY public.poolmirror.id;


--
-- Name: pooltemplate; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.pooltemplate (
    id integer NOT NULL,
    name character varying(64) NOT NULL,
    autocheck public.autocheckmode DEFAULT 'none'::public.autocheckmode NOT NULL,
    lockcheck boolean DEFAULT false NOT NULL,
    growable boolean DEFAULT false NOT NULL,
    unbreakablelevel public.unbreakablelevel DEFAULT 'none'::public.unbreakablelevel NOT NULL,
    rewritable boolean DEFAULT true NOT NULL,
    metadata json DEFAULT '{}'::json NOT NULL,
    createproxy boolean DEFAULT false NOT NULL
);


ALTER TABLE public.pooltemplate OWNER TO storiq;

--
-- Name: pooltemplate_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.pooltemplate_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pooltemplate_id_seq OWNER TO storiq;

--
-- Name: pooltemplate_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.pooltemplate_id_seq OWNED BY public.pooltemplate.id;


--
-- Name: pooltemplatetochecksum; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.pooltemplatetochecksum (
    pooltemplate integer NOT NULL,
    checksum integer NOT NULL
);


ALTER TABLE public.pooltemplatetochecksum OWNER TO storiq;

--
-- Name: pooltochecksum; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.pooltochecksum (
    pool integer NOT NULL,
    checksum integer NOT NULL
);


ALTER TABLE public.pooltochecksum OWNER TO storiq;

--
-- Name: pooltopoolgroup; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.pooltopoolgroup (
    pool integer NOT NULL,
    poolgroup integer NOT NULL
);


ALTER TABLE public.pooltopoolgroup OWNER TO storiq;

--
-- Name: proxy; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.proxy (
    id bigint NOT NULL,
    archivefile bigint NOT NULL,
    status public.proxystatus DEFAULT 'todo'::public.proxystatus NOT NULL
);


ALTER TABLE public.proxy OWNER TO storiq;

--
-- Name: proxy_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.proxy_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.proxy_id_seq OWNER TO storiq;

--
-- Name: proxy_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.proxy_id_seq OWNED BY public.proxy.id;


--
-- Name: report; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.report (
    id bigint NOT NULL,
    "timestamp" timestamp(3) with time zone DEFAULT now() NOT NULL,
    archive bigint,
    media bigint,
    jobrun bigint NOT NULL,
    data json NOT NULL
);


ALTER TABLE public.report OWNER TO storiq;

--
-- Name: report_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.report_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.report_id_seq OWNER TO storiq;

--
-- Name: report_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.report_id_seq OWNED BY public.report.id;


--
-- Name: reports; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.reports (
    id bigint NOT NULL,
    name text NOT NULL,
    report bigint NOT NULL
);


ALTER TABLE public.reports OWNER TO storiq;

--
-- Name: reports_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.reports_id_seq OWNER TO storiq;

--
-- Name: reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.reports_id_seq OWNED BY public.reports.id;


--
-- Name: restoreto; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.restoreto (
    id bigint NOT NULL,
    path character varying(255) DEFAULT '/'::character varying NOT NULL,
    job bigint NOT NULL
);


ALTER TABLE public.restoreto OWNER TO storiq;

--
-- Name: restoreto_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.restoreto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.restoreto_id_seq OWNER TO storiq;

--
-- Name: restoreto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.restoreto_id_seq OWNED BY public.restoreto.id;


--
-- Name: script; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.script (
    id integer NOT NULL,
    name text NOT NULL,
    description text NOT NULL,
    path text NOT NULL,
    type public.scripttype NOT NULL
);


ALTER TABLE public.script OWNER TO storiq;

--
-- Name: script_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.script_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.script_id_seq OWNER TO storiq;

--
-- Name: script_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.script_id_seq OWNED BY public.script.id;


--
-- Name: scripts; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.scripts (
    id integer NOT NULL,
    sequence integer NOT NULL,
    jobtype integer NOT NULL,
    script integer,
    pool integer,
    CONSTRAINT scripts_sequence_check CHECK ((sequence >= 0))
);


ALTER TABLE public.scripts OWNER TO storiq;

--
-- Name: scripts_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.scripts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.scripts_id_seq OWNER TO storiq;

--
-- Name: scripts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.scripts_id_seq OWNED BY public.scripts.id;


--
-- Name: selectedfile_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.selectedfile_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.selectedfile_id_seq OWNER TO storiq;

--
-- Name: selectedfile_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.selectedfile_id_seq OWNED BY public.selectedfile.id;


--
-- Name: userevent; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.userevent (
    id integer NOT NULL,
    event text NOT NULL
);


ALTER TABLE public.userevent OWNER TO storiq;

--
-- Name: userevent_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.userevent_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.userevent_id_seq OWNER TO storiq;

--
-- Name: userevent_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.userevent_id_seq OWNED BY public.userevent.id;


--
-- Name: userlog; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.userlog (
    id bigint NOT NULL,
    login integer NOT NULL,
    "timestamp" timestamp(3) with time zone DEFAULT now() NOT NULL,
    event integer NOT NULL
);


ALTER TABLE public.userlog OWNER TO storiq;

--
-- Name: userlog_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.userlog_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.userlog_id_seq OWNER TO storiq;

--
-- Name: userlog_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.userlog_id_seq OWNED BY public.userlog.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.users (
    id integer NOT NULL,
    login character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    salt character(16) NOT NULL,
    fullname character varying(255),
    email character varying(255) NOT NULL,
    homedirectory text NOT NULL,
    isadmin boolean DEFAULT false NOT NULL,
    canarchive boolean DEFAULT false NOT NULL,
    canrestore boolean DEFAULT false NOT NULL,
    meta json NOT NULL,
    poolgroup integer,
    disabled boolean DEFAULT false NOT NULL,
    key character(512) DEFAULT NULL
);


ALTER TABLE public.users OWNER TO storiq;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO storiq;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vtl; Type: TABLE; Schema: public; Owner: storiq
--

CREATE TABLE public.vtl (
    id integer NOT NULL,
    uuid uuid NOT NULL,
    path character varying(255) NOT NULL,
    prefix character varying(255) NOT NULL,
    nbslots integer NOT NULL,
    nbdrives integer NOT NULL,
    mediaformat integer NOT NULL,
    host integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL,
    CONSTRAINT vtl_check CHECK ((nbslots >= nbdrives)),
    CONSTRAINT vtl_nbdrives_check CHECK ((nbdrives > 0)),
    CONSTRAINT vtl_nbslots_check CHECK ((nbslots > 0))
);


ALTER TABLE public.vtl OWNER TO storiq;

--
-- Name: vtl_id_seq; Type: SEQUENCE; Schema: public; Owner: storiq
--

CREATE SEQUENCE public.vtl_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.vtl_id_seq OWNER TO storiq;

--
-- Name: vtl_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: storiq
--

ALTER SEQUENCE public.vtl_id_seq OWNED BY public.vtl.id;


--
-- Name: application id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.application ALTER COLUMN id SET DEFAULT nextval('public.application_id_seq'::regclass);


--
-- Name: archive id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.archive ALTER COLUMN id SET DEFAULT nextval('public.archive_id_seq'::regclass);


--
-- Name: archivefile id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.archivefile ALTER COLUMN id SET DEFAULT nextval('public.archivefile_id_seq'::regclass);


--
-- Name: archiveformat id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.archiveformat ALTER COLUMN id SET DEFAULT nextval('public.archiveformat_id_seq'::regclass);


--
-- Name: archivemirror id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.archivemirror ALTER COLUMN id SET DEFAULT nextval('public.archivemirror_id_seq'::regclass);


--
-- Name: archivevolume id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.archivevolume ALTER COLUMN id SET DEFAULT nextval('public.archivevolume_id_seq'::regclass);


--
-- Name: backup id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.backup ALTER COLUMN id SET DEFAULT nextval('public.backup_id_seq'::regclass);


--
-- Name: backupvolume id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.backupvolume ALTER COLUMN id SET DEFAULT nextval('public.backupvolume_id_seq'::regclass);


--
-- Name: changer id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.changer ALTER COLUMN id SET DEFAULT nextval('public.changer_id_seq'::regclass);


--
-- Name: checksum id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.checksum ALTER COLUMN id SET DEFAULT nextval('public.checksum_id_seq'::regclass);


--
-- Name: checksumresult id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.checksumresult ALTER COLUMN id SET DEFAULT nextval('public.checksumresult_id_seq'::regclass);


--
-- Name: drive id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.drive ALTER COLUMN id SET DEFAULT nextval('public.drive_id_seq'::regclass);


--
-- Name: driveformat id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.driveformat ALTER COLUMN id SET DEFAULT nextval('public.driveformat_id_seq'::regclass);


--
-- Name: host id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.host ALTER COLUMN id SET DEFAULT nextval('public.host_id_seq'::regclass);


--
-- Name: job id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.job ALTER COLUMN id SET DEFAULT nextval('public.job_id_seq'::regclass);


--
-- Name: jobrecord id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.jobrecord ALTER COLUMN id SET DEFAULT nextval('public.jobrecord_id_seq'::regclass);


--
-- Name: jobrun id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.jobrun ALTER COLUMN id SET DEFAULT nextval('public.jobrun_id_seq'::regclass);


--
-- Name: jobtype id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.jobtype ALTER COLUMN id SET DEFAULT nextval('public.jobtype_id_seq'::regclass);


--
-- Name: log id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.log ALTER COLUMN id SET DEFAULT nextval('public.log_id_seq'::regclass);


--
-- Name: media id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.media ALTER COLUMN id SET DEFAULT nextval('public.media_id_seq'::regclass);


--
-- Name: mediaformat id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.mediaformat ALTER COLUMN id SET DEFAULT nextval('public.mediaformat_id_seq'::regclass);


--
-- Name: medialabel id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.medialabel ALTER COLUMN id SET DEFAULT nextval('public.medialabel_id_seq'::regclass);


--
-- Name: pool id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.pool ALTER COLUMN id SET DEFAULT nextval('public.pool_id_seq'::regclass);


--
-- Name: poolgroup id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.poolgroup ALTER COLUMN id SET DEFAULT nextval('public.poolgroup_id_seq'::regclass);


--
-- Name: poolmirror id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.poolmirror ALTER COLUMN id SET DEFAULT nextval('public.poolmirror_id_seq'::regclass);


--
-- Name: pooltemplate id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.pooltemplate ALTER COLUMN id SET DEFAULT nextval('public.pooltemplate_id_seq'::regclass);


--
-- Name: proxy id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.proxy ALTER COLUMN id SET DEFAULT nextval('public.proxy_id_seq'::regclass);


--
-- Name: report id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.report ALTER COLUMN id SET DEFAULT nextval('public.report_id_seq'::regclass);


--
-- Name: reports id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.reports ALTER COLUMN id SET DEFAULT nextval('public.reports_id_seq'::regclass);


--
-- Name: restoreto id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.restoreto ALTER COLUMN id SET DEFAULT nextval('public.restoreto_id_seq'::regclass);


--
-- Name: script id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.script ALTER COLUMN id SET DEFAULT nextval('public.script_id_seq'::regclass);


--
-- Name: scripts id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.scripts ALTER COLUMN id SET DEFAULT nextval('public.scripts_id_seq'::regclass);


--
-- Name: selectedfile id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.selectedfile ALTER COLUMN id SET DEFAULT nextval('public.selectedfile_id_seq'::regclass);


--
-- Name: userevent id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.userevent ALTER COLUMN id SET DEFAULT nextval('public.userevent_id_seq'::regclass);


--
-- Name: userlog id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.userlog ALTER COLUMN id SET DEFAULT nextval('public.userlog_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vtl id; Type: DEFAULT; Schema: public; Owner: storiq
--

ALTER TABLE ONLY public.vtl ALTER COLUMN id SET DEFAULT nextval('public.vtl_id_seq'::regclass);
