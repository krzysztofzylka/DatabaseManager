<?php

return array (
  'createTable' => 
  array (
    0 => 'CREATE TABLE "users" ("id" BIGSERIAL NOT NULL PRIMARY KEY, "name" CHARACTER VARYING(100) NOT NULL, "age" INTEGER NULL, "price" NUMERIC(10,2) DEFAULT 1.5, "bio" TEXT DEFAULT \'it\'\'s\', "active" SMALLINT DEFAULT 1, "status" CHARACTER VARYING(255) DEFAULT \'a\', "meta" JSONB NULL, "date_created" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, "date_modify" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, "uid" CHARACTER VARYING(36) DEFAULT NULL)',
  ),
  'createTableBigintAutoincrement' => 
  array (
    0 => 'CREATE TABLE "logs" ("id" BIGSERIAL PRIMARY KEY, "code" CHARACTER(3) DEFAULT \'abc\')',
  ),
  'insert' => 
  array (
    0 => 'INSERT INTO "users" ("name", "age") VALUES (:name, :age) RETURNING id',
    1 => 'bind: {":name":"john",":age":3}',
  ),
  'update' => 
  array (
    0 => 'UPDATE "users" SET "name" = :name, "age" = :age WHERE id=5',
    1 => 'bind: {":name":"jane",":age":4}',
  ),
  'updateValue' => 
  array (
    0 => 'UPDATE "users" SET "name" = :name WHERE id=5',
    1 => 'bind: {":name":"jim"}',
  ),
  'delete' => 
  array (
    0 => 'DELETE FROM "users" WHERE id=5',
  ),
  'deleteByConditions' => 
  array (
    0 => 'DELETE FROM "users" WHERE ("name" = :bind_0 AND "users"."age" > :bind_1)',
    1 => 'bind: {":bind_0":"x",":bind_1":3}',
  ),
  'find' => 
  array (
    0 => 'SELECT column_name AS "Field", data_type AS "Type", CASE WHEN is_nullable = \'YES\' THEN \'YES\' ELSE \'NO\' END AS "Null", CASE WHEN column_default IS NOT NULL THEN column_default ELSE \'\' END AS "Default", CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ?) THEN \'PRI\' ELSE \'\' END AS "Key", \'\' AS "Extra" FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position;',
    1 => 'bind: ["users","users"]',
    2 => 'SELECT "users"."id" as "users.id", "users"."name" as "users.name", "users"."status" as "users.status" FROM "users"  WHERE ("users"."age" >= :bind_0 AND "status" IN (:bind_1, :bind_2) AND "created" BETWEEN :bind_3 AND :bind_4 AND ("name" = :bind_5 OR "bio" = :bind_6) AND "users"."bio" IS NOT NULL AND "age" NOT IN (:bind_7, :bind_8)) ORDER BY name DESC LIMIT 1',
    3 => 'bind: {":bind_0":18,":bind_1":"a",":bind_2":"b",":bind_3":"2020-01-01",":bind_4":"2021-01-01",":bind_5":"john",":bind_6":"x",":bind_7":1,":bind_8":2}',
  ),
  'findCustomColumns' => 
  array (
    0 => 'SELECT "users"."id" as "users.id", "users"."name" as "users.name" FROM "users"  WHERE ("id" = :bind_0) LIMIT 1',
    1 => 'bind: {":bind_0":1}',
  ),
  'findAll' => 
  array (
    0 => 'SELECT column_name AS "Field", data_type AS "Type", CASE WHEN is_nullable = \'YES\' THEN \'YES\' ELSE \'NO\' END AS "Null", CASE WHEN column_default IS NOT NULL THEN column_default ELSE \'\' END AS "Default", CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ?) THEN \'PRI\' ELSE \'\' END AS "Key", \'\' AS "Extra" FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position;',
    1 => 'bind: ["users","users"]',
    2 => 'SELECT "users"."id" as "users.id", "users"."name" as "users.name", "users"."status" as "users.status" FROM "users"  WHERE ("active" = :bind_0) GROUP BY "status" ORDER BY "users"."name" DESC, "age" ASC LIMIT 10 OFFSET 5',
    3 => 'bind: {":bind_0":1}',
  ),
  'findAllRawOrderWithFunction' => 
  array (
    0 => 'SELECT "users"."id" as "users.id" FROM "users"  ORDER BY COALESCE("users"."age") ASC, "name" LIMIT 3',
  ),
  'findAllJoin' => 
  array (
    0 => 'SELECT column_name AS "Field", data_type AS "Type", CASE WHEN is_nullable = \'YES\' THEN \'YES\' ELSE \'NO\' END AS "Null", CASE WHEN column_default IS NOT NULL THEN column_default ELSE \'\' END AS "Default", CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ?) THEN \'PRI\' ELSE \'\' END AS "Key", \'\' AS "Extra" FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position;',
    1 => 'bind: ["users","users"]',
    2 => 'SELECT column_name AS "Field", data_type AS "Type", CASE WHEN is_nullable = \'YES\' THEN \'YES\' ELSE \'NO\' END AS "Null", CASE WHEN column_default IS NOT NULL THEN column_default ELSE \'\' END AS "Default", CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ?) THEN \'PRI\' ELSE \'\' END AS "Key", \'\' AS "Extra" FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position;',
    3 => 'bind: ["posts","posts"]',
    4 => 'SELECT column_name AS "Field", data_type AS "Type", CASE WHEN is_nullable = \'YES\' THEN \'YES\' ELSE \'NO\' END AS "Null", CASE WHEN column_default IS NOT NULL THEN column_default ELSE \'\' END AS "Default", CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ?) THEN \'PRI\' ELSE \'\' END AS "Key", \'\' AS "Extra" FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position;',
    5 => 'bind: ["roles","roles"]',
    6 => 'SELECT "users"."id" as "users.id", "users"."name" as "users.name", "users"."status" as "users.status", "posts"."id" as "posts.id", "posts"."name" as "posts.name", "posts"."status" as "posts.status", "r"."id" as "r.id", "r"."name" as "r.name", "r"."status" as "r.status" FROM "users" LEFT JOIN "posts" ON "users"."id" = "posts"."users_id" AND ("published" = :bind_1) INNER JOIN "roles" as "r" ON "users"."role_id" = "r"."id" AND "r"."active" = :bind_2 WHERE ("users"."id" = :bind_0)',
    7 => 'bind: {":bind_0":1,":bind_1":1,":bind_2":1}',
  ),
  'findCount' => 
  array (
    0 => 'SELECT COUNT(*) as "count" FROM "users"  WHERE ("active" = :bind_0)',
    1 => 'bind: {":bind_0":1}',
  ),
  'findCountGrouped' => 
  array (
    0 => 'SELECT COUNT(*) as "count" FROM (SELECT 1 FROM "users"  WHERE ("active" = :bind_0) GROUP BY "status") as "grouped_count"',
    1 => 'bind: {":bind_0":1}',
  ),
  'findIsset' => 
  array (
    0 => 'SELECT COUNT(*) as "count" FROM "users"  WHERE ("name" = :bind_0)',
    1 => 'bind: {":bind_0":"john"}',
  ),
  'columnList' => 
  array (
    0 => 'SELECT column_name AS "Field", data_type AS "Type", CASE WHEN is_nullable = \'YES\' THEN \'YES\' ELSE \'NO\' END AS "Null", CASE WHEN column_default IS NOT NULL THEN column_default ELSE \'\' END AS "Default", CASE WHEN column_name IN (SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ?) THEN \'PRI\' ELSE \'\' END AS "Key", \'\' AS "Extra" FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position;',
    1 => 'bind: ["users","users"]',
  ),
  'exists' => 
  array (
    0 => 'SELECT 1 FROM information_schema.tables WHERE table_schema = \'public\' AND table_name = ? LIMIT 1;',
    1 => 'bind: ["users"]',
  ),
  'getTables' => 
  array (
    0 => 'SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\'',
  ),
  'createIndex' => 
  array (
    0 => 'CREATE INDEX "index_users_name_age" ON "users"("name", "age")',
  ),
  'alterTable' => 
  array (
    0 => 'ALTER TABLE "users" ADD "nick" CHARACTER VARYING(50) NULL AFTER "name";
ALTER TABLE "users" DROP COLUMN "bio";
ALTER TABLE "users" MODIFY "age" int(11) NOT NULL;
ALTER TABLE "users" RENAME COLUMN "nick" TO "nickname";',
  ),
  'isColumnUnique' => 
  array (
    0 => 'SELECT COUNT(*) FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey) WHERE c.conrelid = to_regclass(?) AND c.contype = \'u\' AND a.attname = ?',
    1 => 'bind: ["users","name"]',
  ),
);
