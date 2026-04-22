import os
from playwright.sync_api import sync_playwright

# 🔧 CONFIGURACIÓN
BASE_URL = "http://localhost/LIGA_FUTBOL"  # CAMBIA SI ES NECESARIO
LOGIN_URL = f"{BASE_URL}/index.php?route=login"  # ajusta si es diferente

USER = "Admin"
PASSWORD = "123456"

# 📁 RUTAS
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
SCREENSHOT_DIR = os.path.join(BASE_DIR, "..", "screenshots")
os.makedirs(SCREENSHOT_DIR, exist_ok=True)


def take_screenshot(page, name):
    path = os.path.join(SCREENSHOT_DIR, f"{name}.png")
    page.screenshot(path=path, full_page=True)
    print(f"✅ Captura guardada: {name}.png")


def run(playwright):
    browser = playwright.chromium.launch(headless=False)

    context = browser.new_context(
        viewport={"width": 1920, "height": 1080}
    )

    page = context.new_page()

    # -----------------------
    # 🔐 LOGIN
    # -----------------------
    print("🔐 Iniciando sesión...")
    page.goto(LOGIN_URL)

    # ⚠️ Ajusta estos selectores según tu HTML
    page.fill('input[name="username"]', USER)
    page.fill('input[name="password"]', PASSWORD)
    page.click('button[type="submit"]')

    page.wait_for_timeout(3000)

    take_screenshot(page, "dashboard")

    # -----------------------
    # 📊 MÓDULOS
    # -----------------------

    pages = [
        ("equipos", "equipos"),
        ("jugadores", "jugadores"),
        ("partidos", "partidos-resultados"),
        ("finanzas", "finanzas"),
        ("usuarios", "usuarios"),
        ("configuracion", "configuracion"),
        ("canchas", "canchas"),
        ("temporadas", "temporadas")
    ]

    for name, route in pages:
        print(f"📸 Capturando {name}...")
        page.goto(f"{BASE_URL}/{route}")
        page.wait_for_timeout(2500)
        take_screenshot(page, name)

    browser.close()


if __name__ == "__main__":
    with sync_playwright() as playwright:
        run(playwright)