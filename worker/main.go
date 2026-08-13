package main

import (
	"database/sql"
	"log"
	"os"
	"time"

	_ "github.com/lib/pq"
)

func main() {
	dsn := os.Getenv("DATABASE_URL")
	if dsn == "" {
		host := getEnv("DB_HOST", "db")
		port := getEnv("DB_PORT", "5432")
		user := getEnv("DB_USERNAME", "sammy")
		password := getEnv("DB_PASSWORD", "546546")
		dbname := getEnv("DB_DATABASE", "sert_manager")
		sslmode := getEnv("DB_SSLMODE", "disable")
		dsn = "host=" + host + " port=" + port + " user=" + user + " password=" + password + " dbname=" + dbname + " sslmode=" + sslmode
	}

	db, err := sql.Open("postgres", dsn)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	defer db.Close()

	if err := db.Ping(); err != nil {
		log.Fatalf("Failed to ping database: %v", err)
	}

	log.Println("Worker started. Checking for expired certificates every minute...")

	ticker := time.NewTicker(1 * time.Minute)
	defer ticker.Stop()

	// Run immediately on start
	checkExpired(db)

	for range ticker.C {
		checkExpired(db)
	}
}

func checkExpired(db *sql.DB) {
	query := `
WITH updated AS (
    UPDATE certificates
    SET status = 'expired', updated_at = NOW()
    WHERE status = 'active' AND expires_at < CURRENT_DATE AND deleted_at IS NULL
    RETURNING id, name
)
INSERT INTO activity_logs (subject_type, subject_id, user_id, action, changes, created_at)
SELECT 'App\Models\Certificate', id, NULL, 'expired',
       jsonb_build_object('before', jsonb_build_object('status', 'active'),
                          'after', jsonb_build_object('status', 'expired')),
       NOW()
FROM updated
`
	result, err := db.Exec(query)
	if err != nil {
		log.Printf("ERROR: failed to update expired certificates: %v", err)
		return
	}

	rowsAffected, _ := result.RowsAffected()
	if rowsAffected > 0 {
		log.Printf("Updated %d certificate(s) to expired status (audit logged)", rowsAffected)
	}
}

func getEnv(key, fallback string) string {
	if value, ok := os.LookupEnv(key); ok {
		return value
	}
	return fallback
}
