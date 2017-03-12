CREATE TABLE "user" (
	"user_id"    BIGSERIAL PRIMARY KEY,
	"uuid"       UUID NOT NULL DEFAULT gen_random_uuid(),
	"first_name" TEXT,
	"last_name"  TEXT,
	"email"      VARCHAR(256) UNIQUE,
	"session"    VARCHAR(128),
	"created"    TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
	"status"     BOOLEAN NOT NULL DEFAULT true
);
