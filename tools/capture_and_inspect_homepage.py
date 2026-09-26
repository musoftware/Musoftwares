import os
import sys
import time
from playwright.sync_api import sync_playwright

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), 'screenshots')
os.makedirs(OUTPUT_DIR, exist_ok=True)

URL = "http://127.0.0.1:8000/"

def capture_homepage():
    print(f"Launching browser to capture {URL}...")
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            viewport={"width": 1440, "height": 900},
            device_scale_factor=1.5
        )
        page = context.new_page()
        page.goto(URL, wait_until="networkidle")
        time.sleep(1)

        # First fold screenshot (exact 100vh fold with navbar + hero)
        fold_path = os.path.join(OUTPUT_DIR, "first_fold_100vh.png")
        page.screenshot(path=fold_path, full_page=False)
        print(f"Saved first fold screenshot to: {fold_path}")

        # Full page screenshot
        full_path = os.path.join(OUTPUT_DIR, "full_page.png")
        page.screenshot(path=full_path, full_page=True)
        print(f"Saved full page screenshot to: {full_path}")

        # Section-by-section screenshots for detailed analysis
        sections = [
            ("hero", "section:nth-of-type(1)"),
            ("services", "#services-overview"),
            ("about", "section:nth-of-type(3)"),
            ("testimonials", "section:nth-of-type(4)"),
            ("case_studies", "#case-studies"),
            ("way_of_building", "section:nth-of-type(6)"),
            ("capabilities", "section:nth-of-type(7)"),
            ("tech_stacks", "#tech-stack-section"),
            ("how_it_works", "#how-it-works"),
            ("featured_services", "#featured-services-carousel"),
            ("cta", "#cta-hire"),
            ("footer", "footer")
        ]

        for name, selector in sections:
            try:
                el = page.locator(selector).first
                if el.count() > 0:
                    sec_path = os.path.join(OUTPUT_DIR, f"section_{name}.png")
                    el.screenshot(path=sec_path)
                    print(f"Captured {name} -> {sec_path}")
            except Exception as e:
                print(f"Error capturing {name}: {e}")

        browser.close()

if __name__ == "__main__":
    capture_homepage()
