from locust import HttpUser, task, between

class JugajugLoadTestUser(HttpUser):
    wait_time = between(1, 3)

    @task(3)
    def view_feed(self):
        self.client.get("/api/v1/feed")

    @task(2)
    def check_health(self):
        self.client.get("/api/v1/health")

    @task(1)
    def search_trending(self):
        self.client.get("/api/v1/search?query=bangladesh")
