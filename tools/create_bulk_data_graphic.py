import os
from PIL import Image, ImageDraw, ImageFont

def create_bulk_data_mockup():
    width, height = 800, 800
    bg_color = (225, 245, 238) # Soft mint pastel background
    img = Image.new("RGBA", (width, height), bg_color)
    draw = ImageDraw.Draw(img)

    # Load fonts
    try:
        font_title = ImageFont.truetype("arialbd.ttf", 22)
        font_sub = ImageFont.truetype("arial.ttf", 14)
        font_card_num = ImageFont.truetype("arialbd.ttf", 20)
        font_card_lbl = ImageFont.truetype("arial.ttf", 11)
        font_table_hdr = ImageFont.truetype("arialbd.ttf", 12)
        font_table_row = ImageFont.truetype("arial.ttf", 12)
        font_badge = ImageFont.truetype("arialbd.ttf", 10)
    except:
        font_title = font_sub = font_card_num = font_card_lbl = font_table_hdr = font_table_row = font_badge = ImageFont.load_default()

    # Draw soft shadow for the window
    win_x, win_y, win_w, win_h = 60, 90, 680, 620
    shadow_offset = 12
    for offset in range(shadow_offset, 0, -2):
        alpha = int(25 * (1 - offset / shadow_offset))
        shadow_rect = [win_x - offset, win_y - offset, win_x + win_w + offset, win_y + win_h + offset]
        draw.rounded_rectangle(shadow_rect, radius=20, fill=(0, 40, 20, alpha))

    # Main Window Container
    win_rect = [win_x, win_y, win_x + win_w, win_y + win_h]
    draw.rounded_rectangle(win_rect, radius=16, fill=(255, 255, 255, 255), outline=(210, 230, 220), width=1)

    # Window Header Bar
    hdr_h = 44
    draw.rounded_rectangle([win_x, win_y, win_x + win_w, win_y + hdr_h + 10], radius=16, fill=(245, 248, 246))
    draw.rectangle([win_x, win_y + 24, win_x + win_w, win_y + hdr_h], fill=(245, 248, 246))
    draw.line([win_x, win_y + hdr_h, win_x + win_w, win_y + hdr_h], fill=(225, 235, 230), width=1)

    # Window Dots
    draw.ellipse([win_x + 18, win_y + 17, win_x + 28, win_y + 27], fill=(255, 95, 86))
    draw.ellipse([win_x + 36, win_y + 17, win_x + 46, win_y + 27], fill=(255, 189, 46))
    draw.ellipse([win_x + 54, win_y + 17, win_x + 64, win_y + 27], fill=(39, 201, 63))

    # Window Title
    title_text = "Bulk Data Automation System • Master Console"
    draw.text((win_x + 80, win_y + 14), title_text, fill=(70, 85, 80), font=font_sub)

    # Sub-header / Breadcrumbs & Status
    sub_y = win_y + hdr_h + 16
    draw.text((win_x + 24, sub_y), "Distributed Cluster > 16 Desktop Worker Nodes", fill=(30, 41, 59), font=font_title)
    
    # Live Badge
    badge_x = win_x + win_w - 150
    draw.rounded_rectangle([badge_x, sub_y + 2, badge_x + 126, sub_y + 24], radius=6, fill=(220, 252, 231))
    draw.ellipse([badge_x + 10, sub_y + 9, badge_x + 18, sub_y + 17], fill=(22, 163, 74))
    draw.text((badge_x + 24, sub_y + 6), "CONCURRENCY ACTIVE", fill=(21, 128, 61), font=font_badge)

    # Metrics Row (4 Cards)
    cards_y = sub_y + 44
    card_w = (win_w - 48 - 36) // 4
    card_h = 76
    metrics = [
        ("TOTAL RECORDS", "4,850,000", "+12.4k/min", (15, 118, 110)),
        ("ACTIVE WORKERS", "16 Bots", "Zero Lock Contention", (2, 132, 199)),
        ("ELASTICSEARCH", "8.2 ms", "Sub-second query", (217, 119, 6)),
        ("PROXY ROTATION", "99.8%", "0 Bans Detected", (22, 163, 74))
    ]

    for i, (lbl, val, note, col) in enumerate(metrics):
        cx = win_x + 24 + i * (card_w + 12)
        draw.rounded_rectangle([cx, cards_y, cx + card_w, cards_y + card_h], radius=10, fill=(248, 250, 252), outline=(226, 232, 240), width=1)
        draw.text((cx + 12, cards_y + 10), lbl, fill=(100, 116, 139), font=font_card_lbl)
        draw.text((cx + 12, cards_y + 26), val, fill=(15, 23, 42), font=font_card_num)
        draw.text((cx + 12, cards_y + 54), note, fill=col, font=font_badge)

    # Middle Content: Split into Worker Nodes & Live Ingestion Table
    content_y = cards_y + card_h + 18
    content_h = 240

    # Left Box: Automation Worker Bots (C# .NET)
    left_w = 190
    draw.rounded_rectangle([win_x + 24, content_y, win_x + 24 + left_w, content_y + content_h], radius=10, fill=(248, 250, 252), outline=(226, 232, 240), width=1)
    draw.text((win_x + 34, content_y + 14), "Worker Bots (C# .NET)", fill=(15, 23, 42), font=font_table_hdr)
    
    workers = [
        ("Worker-01 (US-East)", "2,400 req/m", "Running"),
        ("Worker-02 (EU-West)", "2,150 req/m", "Running"),
        ("Worker-03 (Proxy-Rot)", "1,980 req/m", "Rotating"),
        ("Worker-04 (Lock-Sync)", "2,300 req/m", "Running"),
        ("Worker-05 (Batch-Ingest)", "2,520 req/m", "Running"),
    ]
    for idx, (w_name, w_spd, w_st) in enumerate(workers):
        wy = content_y + 40 + idx * 38
        draw.line([win_x + 34, wy - 4, win_x + 24 + left_w - 12, wy - 4], fill=(235, 240, 245), width=1)
        draw.text((win_x + 34, wy + 2), w_name, fill=(51, 65, 85), font=font_table_row)
        draw.text((win_x + 34, wy + 16), w_spd, fill=(100, 116, 139), font=font_badge)
        
        st_color = (22, 163, 74) if w_st == "Running" else (217, 119, 6)
        draw.rounded_rectangle([win_x + left_w - 44, wy + 4, win_x + left_w + 14, wy + 22], radius=4, fill=(240, 245, 240))
        draw.text((win_x + left_w - 38, wy + 7), w_st, fill=st_color, font=font_badge)

    # Right Box: Master Ingestion Stream
    right_x = win_x + 24 + left_w + 14
    right_w = win_w - 48 - left_w - 14
    draw.rounded_rectangle([right_x, content_y, right_x + right_w, content_y + content_h], radius=10, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    
    # Table Header
    draw.rounded_rectangle([right_x, content_y, right_x + right_w, content_y + 36], radius=10, fill=(241, 245, 249))
    draw.rectangle([right_x, content_y + 20, right_x + right_w, content_y + 36], fill=(241, 245, 249))
    draw.text((right_x + 14, content_y + 11), "TASK / BATCH ID", fill=(100, 116, 139), font=font_badge)
    draw.text((right_x + 130, content_y + 11), "RECORDS", fill=(100, 116, 139), font=font_badge)
    draw.text((right_x + 204, content_y + 11), "PROXY ROTATION", fill=(100, 116, 139), font=font_badge)
    draw.text((right_x + 314, content_y + 11), "STATUS", fill=(100, 116, 139), font=font_badge)

    batches = [
        ("TASK-98410-X", "50,000", "194.26.xx • Rotated", "SYNCED 100%"),
        ("TASK-98411-B", "100,000", "185.12.xx • Clean", "PROCESSING 74%"),
        ("TASK-98412-M", "25,000", "91.240.xx • Clean", "COMPLETED"),
        ("TASK-98413-Z", "75,000", "193.34.xx • Rotated", "PROCESSING 42%"),
        ("TASK-98414-Q", "200,000", "176.10.xx • Clean", "QUEUED (LOCKED)"),
    ]

    for idx, (tid, recs, prx, st) in enumerate(batches):
        by = content_y + 44 + idx * 37
        draw.line([right_x + 10, by - 4, right_x + right_w - 10, by - 4], fill=(241, 245, 249), width=1)
        draw.text((right_x + 14, by + 4), tid, fill=(15, 23, 42), font=font_table_row)
        draw.text((right_x + 130, by + 4), recs, fill=(51, 65, 85), font=font_table_row)
        draw.text((right_x + 204, by + 4), prx, fill=(100, 116, 139), font=font_table_row)
        
        col = (22, 163, 74) if "COMPLETED" in st or "SYNCED" in st else ((2, 132, 199) if "PROCESSING" in st else (100, 116, 139))
        draw.text((right_x + 314, by + 4), st, fill=col, font=font_badge)

    # Bottom Footer Strip inside Window
    foot_y = content_y + content_h + 16
    foot_h = 52
    draw.rounded_rectangle([win_x + 24, foot_y, win_x + win_w - 24, foot_y + foot_h], radius=10, fill=(240, 253, 244), outline=(187, 247, 208), width=1)
    draw.text((win_x + 36, foot_y + 11), "INTEGRATION HIGHLIGHTS:", fill=(22, 101, 52), font=font_badge)
    draw.text((win_x + 36, foot_y + 26), "Laravel Backend • Elasticsearch 8.x • C# .NET Workers • Telegram Alerts • Zero Lock Contention", fill=(21, 128, 61), font=font_table_row)

    # Save image
    out_path = "public/images/portfolio/case_study_bulk_data_square.png"
    img.save(out_path, "PNG", quality=95)
    print(f"Mockup saved to {out_path}")

if __name__ == "__main__":
    create_bulk_data_mockup()
