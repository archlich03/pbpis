import json

with open('./lt.json') as f:
    data = json.load(f)

sorted_data = {key: data[key] for key in sorted(data.keys())}

with open('./lt-sorted.json', 'w') as f:
    json.dump(sorted_data, f, ensure_ascii=False, indent=2)

print("JSON successfully sorted.")
