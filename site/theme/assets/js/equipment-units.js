/** Convert only complete, explicit measurements. Unknown values stay unknown. */
export function displayMeasurement(value, unitSystem = 'metric') {
  if (unitSystem !== 'imperial' || typeof value !== 'string') return value;
  const match = value.trim().match(/^([+-]?\d+(?:\.\d+)?)\s*(kg|t|mm|cm|m|kw|m3|m³|l)$/i);
  if (!match) return value;
  const number = Number(match[1]);
  const conversions = {
    kg: [2.2046226218, 'lb'], t: [2204.6226218, 'lb'],
    mm: [0.03937007874, 'in'], cm: [0.3937007874, 'in'], m: [3.280839895, 'ft'],
    kw: [1.34102209, 'hp'], m3: [1.307950619, 'yd³'], 'm³': [1.307950619, 'yd³'],
    l: [0.2641720524, 'US gal'],
  };
  const [factor, unit] = conversions[match[2].toLowerCase()];
  const converted = Math.round(number * factor * 100) / 100;
  return converted.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' ' + unit;
}
