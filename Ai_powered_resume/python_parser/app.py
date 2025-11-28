from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route('/health')
def health():
    return jsonify({'status':'ok'})

@app.route('/parse', methods=['POST'])
def parse():
    data = request.get_json() or {}
    text = data.get('text', '')
    # Very simple heuristic parser stub. Replace with spaCy-powered parser.
    parsed = {
        'name': None,
        'contact': None,
        'skills': [],
        'experience': [],
        'education': []
    }
    if text:
        lines = [l.strip() for l in text.splitlines() if l.strip()]
        # naive name detection: first non-empty line
        parsed['name'] = lines[0] if lines else None
        # naive skills detection: look for 'skills:' prefix or common tech words
        skills = []
        for l in lines:
            low = l.lower()
            if low.startswith('skills:') or 'skills' in low:
                parts = l.split(':',1)[1]
                skills = [s.strip() for s in parts.split(',') if s.strip()]
        # fallback keyword scan
        for kw in ['php','mysql','docker','aws','rest','api','python','javascript']:
            if kw in text.lower() and kw not in skills:
                skills.append(kw)
        parsed['skills'] = skills
        # experience stub: find lines with years
        exp = []
        for l in lines:
            if any(c.isdigit() for c in l) and ('-' in l or '–' in l):
                exp.append({'raw': l})
        parsed['experience'] = exp
    return jsonify(parsed)

if __name__ == '__main__':
    app.run(debug=True)
