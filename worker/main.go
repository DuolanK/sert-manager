// The background worker that auto-expired certificates has been removed:
// the app is now a task manager and no longer needs a periodic expiry job.
// This file is kept as a placeholder only.
package main

import "log"

func main() {
	log.Println("worker removed: task manager requires no background expiry job")
}
