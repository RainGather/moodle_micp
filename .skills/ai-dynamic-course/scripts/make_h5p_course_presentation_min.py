#!/usr/bin/env python3
"""Create a minimal H5P CoursePresentation .h5p (ZIP) from a slide spec.

This is intentionally minimal: only h5p.json + content/content.json.
It assumes the Moodle site already has the needed H5P libraries installed.

Inputs:
- --out <path.h5p>
- --title <title>
- --lang zh-cn
- --slides-json <slides.json> (root: {"slides": [...]})

Optional:
- --sample-root <dir> pointing to a decompressed H5P export root (historically: moodle_h5p/sample)
  If provided, we will read base h5p.json and override/l10n defaults from it.
  If omitted, embedded defaults are used.

The slides format uses a tiny DSL to avoid hand-authoring H5P internals.
"""

from __future__ import annotations

import argparse
import json
import uuid
import zipfile
from pathlib import Path


_DEFAULT_H5P_JSON = {
    "title": "H5P Course Presentation",
    "language": "und",
    "mainLibrary": "H5P.CoursePresentation",
    "embedTypes": ["div"],
    "license": "U",
    "defaultLanguage": "zh-cn",
    "preloadedDependencies": [
        {"machineName": "H5P.AdvancedText", "majorVersion": "1", "minorVersion": "1"},
        {"machineName": "H5P.Image", "majorVersion": "1", "minorVersion": "1"},
        {"machineName": "H5P.MultiChoice", "majorVersion": "1", "minorVersion": "16"},
        {"machineName": "FontAwesome", "majorVersion": "4", "minorVersion": "5"},
        {"machineName": "H5P.JoubelUI", "majorVersion": "1", "minorVersion": "3"},
        {"machineName": "H5P.FontIcons", "majorVersion": "1", "minorVersion": "0"},
        {"machineName": "H5P.Transition", "majorVersion": "1", "minorVersion": "0"},
        {"machineName": "H5P.Question", "majorVersion": "1", "minorVersion": "5"},
        {"machineName": "H5P.DragQuestion", "majorVersion": "1", "minorVersion": "14"},
        {"machineName": "jQuery.ui", "majorVersion": "1", "minorVersion": "10"},
        {
            "machineName": "H5P.CoursePresentation",
            "majorVersion": "1",
            "minorVersion": "25",
        },
    ],
}

_DEFAULT_OVERRIDE = {
    "activeSurface": False,
    "hideSummarySlide": False,
    "summarySlideSolutionButton": True,
    "summarySlideRetryButton": True,
    "enablePrintButton": False,
    "social": {
        "showFacebookShare": False,
        "facebookShare": {
            "url": "",
            "quote": "",
        },
        "showTwitterShare": False,
        "twitterShare": {
            "statement": "",
            "hashtag": "",
            "url": "",
        },
        "showGoogleShare": False,
    },
}

_DEFAULT_L10N = {
    "slide": "Slide",
    "score": "Score",
    "yourScore": "Your Score",
    "maxScore": "Max Score",
    "total": "Total",
    "totalScore": "Total Score",
    "showSolutions": "Show solutions",
    "retry": "Retry",
    "exportAnswers": "Export text",
    "hideKeywords": "Hide keywords",
    "showKeywords": "Show keywords",
    "fullscreen": "Fullscreen",
    "exitFullscreen": "Exit fullscreen",
    "prevSlide": "Previous slide",
    "nextSlide": "Next slide",
    "currentSlide": "Current slide",
    "lastSlide": "Last slide",
    "solutionModeTitle": "Solution Mode",
    "solutionModeText": "Exit solution mode",
    "summaryMultipleTaskText": "Multiple tasks",
    "scoreMessage": "You achieved:",
    "shareFacebook": "Share on Facebook",
    "shareTwitter": "Share on Twitter",
    "shareGoogle": "Share on Google+",
    "summary": "Summary",
    "solutionsButtonTitle": "Show solutions",
    "printTitle": "Print",
    "printIngress": "How would you like to print this presentation?",
    "printAllSlides": "Print all slides",
    "printCurrentSlide": "Print current slide",
    "noTitle": "No title",
    "accessibilitySlideNavigationExplanation": "Use left and right arrow to change slide",
    "accessibilityCanvasLabel": "Presentation slide",
    "accessibilityProgressBarLabel": "Slide progress",
    "containsNotCompleted": "Contains not completed interaction",
    "containsCompleted": "Contains completed interaction",
    "slideCount": "Slide :num of :total",
    "containsOnlyCorrect": "Contains only correct answers",
    "containsIncorrectAnswers": "Contains incorrect answers",
    "shareResult": "Share Result",
    "accessibilityTotalScore": "You got :score of :maxScore points in total",
    "accessibilityEnteredFullscreen": "Entered fullscreen",
    "accessibilityExitedFullscreen": "Exited fullscreen",
    "confirmDialogHeader": "Please confirm",
    "confirmDialogText": "This will submit your results, do you want to continue?",
    "confirmDialogConfirmText": "Submit and continue",
    "slideshowNavigationLabel": "Slideshow navigation",
}


def _sid() -> str:
    return str(uuid.uuid4())


def _text_element(x: float, y: float, w: float, h: float, html: str) -> dict:
    return {
        "x": x,
        "y": y,
        "width": w,
        "height": h,
        "action": {
            "library": "H5P.AdvancedText 1.1",
            "params": {"text": html},
            "subContentId": _sid(),
            "metadata": {"contentType": "Text", "license": "U", "title": "未命名Text"},
        },
        "alwaysDisplayComments": False,
        "backgroundOpacity": 0,
        "displayAsButton": False,
        "buttonSize": "big",
        "goToSlideType": "specified",
        "invisible": False,
        "solution": "",
    }


def build_content(sample_content: dict, slides_spec: list[dict]) -> dict:
    slides = []
    for s in slides_spec:
        elements = []
        for el in s.get("elements", []):
            if el["type"] == "text":
                elements.append(
                    _text_element(
                        el.get("x", 8),
                        el.get("y", 10),
                        el.get("w", 84),
                        el.get("h", 70),
                        el["html"],
                    )
                )
            else:
                raise ValueError(f"Unsupported element type: {el['type']}")

        slides.append({"slideBackgroundSelector": {}, "elements": elements})

    return {
        "presentation": {
            "slides": slides,
            "keywordListEnabled": False,
            "globalBackgroundSelector": {},
            "keywordListAlwaysShow": False,
            "keywordListAutoHide": False,
            "keywordListOpacity": 0,
        },
        "override": sample_content.get("override", {}),
        "l10n": sample_content.get("l10n", {}),
    }


def _load_sample_defaults(sample_root: Path) -> tuple[dict, dict]:
    base_h5p = json.loads((sample_root / "h5p.json").read_text("utf-8"))
    sample_content = json.loads(
        (sample_root / "content" / "content.json").read_text("utf-8")
    )
    return base_h5p, sample_content


def _embedded_defaults() -> tuple[dict, dict]:
    base_h5p = dict(_DEFAULT_H5P_JSON)
    sample_content = {"override": _DEFAULT_OVERRIDE, "l10n": _DEFAULT_L10N}
    return base_h5p, sample_content


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--out", required=True)
    ap.add_argument("--title", required=True)
    ap.add_argument("--lang", default="zh-cn")
    ap.add_argument("--slides-json", required=True)
    ap.add_argument(
        "--sample-root",
        help=(
            "Path to a decompressed H5P export root containing h5p.json and content/content.json. "
            "If omitted, embedded defaults are used."
        ),
    )
    args = ap.parse_args()

    if args.sample_root:
        base_h5p, sample_content = _load_sample_defaults(Path(args.sample_root))
    else:
        base_h5p, sample_content = _embedded_defaults()

    slides_obj = json.loads(Path(args.slides_json).read_text("utf-8"))
    content = build_content(sample_content, slides_obj["slides"])

    h5p_json = dict(base_h5p)
    h5p_json["title"] = args.title
    h5p_json["language"] = args.lang
    h5p_json["defaultLanguage"] = args.lang

    out = Path(args.out)
    out.parent.mkdir(parents=True, exist_ok=True)

    with zipfile.ZipFile(out, "w", compression=zipfile.ZIP_DEFLATED) as z:
        z.writestr("h5p.json", json.dumps(h5p_json, ensure_ascii=False))
        z.writestr("content/content.json", json.dumps(content, ensure_ascii=False))


if __name__ == "__main__":
    main()
