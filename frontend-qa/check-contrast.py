#!/usr/bin/env python3
"""Validateur de contraste WCAG des tokens Auto-GestBoard.

Pire cas : chaque couleur du canevas est testee SOUS le panneau/verre (composition alpha).
A re-executer a chaque modification de tokens.css (reporter les valeurs ici).
"""
import sys


def rgb(h):
    h = h.lstrip('#')
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def lin(c):
    c /= 255
    return c / 12.92 if c <= .03928 else ((c + .055) / 1.055) ** 2.4


def lum(c):
    r, g, b = map(lin, c)
    return .2126 * r + .7152 * g + .0722 * b


def cr(a, b):
    la, lb = sorted((lum(a), lum(b)), reverse=True)
    return (la + .05) / (lb + .05)


def over(hexv, a, bg):
    f = rgb(hexv)
    return tuple(round(f[i] * a + bg[i] * (1 - a)) for i in range(3))


# mode, canevas (base + bulles), panneau (couleur, alpha), verre (couleur, alpha), solide,
# t1,t2,t3, bordure controle, accent, on-accent
T = {
    "aurore":     ("light", ["#EEF0FF", "#C7D2FE", "#FBCFE8", "#99F6E4"], ("#FFFFFF", .72), ("#FFFFFF", .78), "#FFFFFF", "#0F172A", "#475569", "#5B6577", "#6B7385", "#4F46E5", "#FFFFFF"),
    "ocean":      ("light", ["#E8F4FB", "#BAE6FD", "#A5F3FC", "#BFDBFE"], ("#FFFFFF", .72), ("#FFFFFF", .78), "#FFFFFF", "#0B1B2B", "#425569", "#56677A", "#667A8E", "#0369A1", "#FFFFFF"),
    "sable":      ("light", ["#FBF5EC", "#FDE68A", "#FED7AA", "#FECDD3"], ("#FFFFFF", .72), ("#FFFFFF", .78), "#FFFFFF", "#1C1917", "#57534E", "#6A645F", "#78716C", "#B83C0A", "#FFFFFF"),
    "foret":      ("dark",  ["#06161A", "#0E4F45", "#0B3B3B", "#07201F"], ("#0A2224", .74), ("#0A2224", .80), "#0E2426", "#ECFDF5", "#B7D3CA", "#A0C0B6", "#7FAA9D", "#34D399", "#04251A"),
    "crepuscule": ("dark",  ["#0B0A22", "#3B1A8C", "#1A1040", "#0B1633"], ("#140E34", .74), ("#140E34", .80), "#171238", "#F1EEFF", "#C6BFEA", "#B0A8DB", "#8F86C6", "#A78BFA", "#0B1020"),
    "graphite":   ("dark",  ["#0B0D10", "#273244", "#12151A", "#0F1217"], ("#11151A", .76), ("#11151A", .80), "#14181E", "#F3F4F6", "#BCC2CC", "#A5ACB8", "#8791A3", "#38BDF8", "#0B1020"),
}
ACC = {"light": {"indigo": "#4F46E5", "violet": "#7C3AED", "rose": "#BE123C", "orange": "#B83C0A", "amber": "#9A5006", "emerald": "#047857", "teal": "#0F766E", "sky": "#0369A1"},
       "dark":  {"indigo": "#818CF8", "violet": "#A78BFA", "rose": "#FB7185", "orange": "#FB923C", "amber": "#FBBF24", "emerald": "#34D399", "teal": "#2DD4BF", "sky": "#38BDF8"}}
ST = {"light": {"success": "#166534", "warning": "#854D0E", "danger": "#B91C1C", "info": "#075985"},
      "dark":  {"success": "#86EFAC", "warning": "#FCD34D", "danger": "#FCA5A5", "info": "#7DD3FC"}}
SOLID = {"light": ({"success": "#15803D", "warning": "#B45309", "danger": "#DC2626", "info": "#0369A1"}, "#FFFFFF"),
         "dark":  ({"success": "#4ADE80", "warning": "#FBBF24", "danger": "#F87171", "info": "#38BDF8"}, "#0B1020")}
ON_ACC = {"light": "#FFFFFF", "dark": "#0B1020"}
CHART = {"light": ["#0E7490", "#B45309", "#BE185D", "#4D7C0F", "#6B7280"],
         "dark": ["#22D3EE", "#FBBF24", "#F472B6", "#A3E635", "#9CA3AF"]}

fails, n = [], 0


def chk(label, v, need=4.5):
    global n
    n += 1
    if v < need:
        fails.append(f"{label}: {v:.2f} < {need}")


for name, (mode, cvs, panel, glass, solid, t1, t2, t3, ctl, acc, onacc) in T.items():
    chk(f"{name} on-accent/accent", cr(rgb(onacc), rgb(acc)))
    for c in cvs:
        base = rgb(c)
        P = over(*panel, base)
        G = over(*glass, base)
        for lab, bg in (("panel", P), ("glass", G)):
            for tn, t in (("t1", t1), ("t2", t2), ("t3", t3)):
                chk(f"{name}/{c} {tn} on {lab}", cr(rgb(t), bg))
            chk(f"{name}/{c} accent-text on {lab}", cr(rgb(acc), bg))
            for an, a in ACC[mode].items():
                chk(f"{name}/{c} accent {an} text on {lab}", cr(rgb(a), bg))
            for sn, s in ST[mode].items():
                chk(f"{name}/{c} {sn} text on {lab}", cr(rgb(s), bg))
                chk(f"{name}/{c} {sn} text on soft/{lab}", cr(rgb(s), over(SOLID[mode][0][sn], .16, bg)))
        chk(f"{name}/{c} control-border vs panel (3:1)", cr(rgb(ctl), P), 3.0)
    for tn, t in (("t1", t1), ("t2", t2), ("t3", t3)):
        chk(f"{name} {tn} on solid", cr(rgb(t), rgb(solid)))
    chk(f"{name} control-border vs solid (3:1)", cr(rgb(ctl), rgb(solid)), 3.0)
    for k, s_ in enumerate(CHART[mode], 2):
        chk(f"{name} chart-{k} vs solid (3:1)", cr(rgb(s_), rgb(solid)), 3.0)
for mode in ("light", "dark"):
    for an, a in ACC[mode].items():
        chk(f"{mode} on-accent/{an}", cr(rgb(ON_ACC[mode]), rgb(a)))
    sol, on = SOLID[mode]
    for sn, s in sol.items():
        chk(f"{mode} on-solid/{sn}", cr(rgb(on), rgb(s)))

print(f"{n} verifications, {len(fails)} echec(s)")
for f in fails:
    print(" -", f)
sys.exit(1 if fails else 0)
