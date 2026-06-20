export const buildGradient = (segments) => {
    if (!segments.length) {
        return 'conic-gradient(#416b5c 0deg 360deg)';
    }

    const segmentAngle = 360 / segments.length;

    return `conic-gradient(${segments
        .map((segment, index) => {
            const start = index * segmentAngle;
            const end = start + segmentAngle;
            const color = index % 2 === 0 ? '#416b5c' : '#f5f1dd';
            return `${color} ${start}deg ${end}deg`;
        })
        .join(', ')})`;
};

export const computeSpinRotation = (currentRotation, winnerIndex, segmentCount, extraTurns = 5 * 360) => {
    const safeCount = Math.max(segmentCount, 1);
    const segmentAngle = 360 / safeCount;
    const targetAngle = winnerIndex * segmentAngle + segmentAngle / 2;
    const desiredRotation = (360 - targetAngle) % 360;
    const currentNormalized = ((currentRotation % 360) + 360) % 360;
    const delta = (desiredRotation - currentNormalized + 360) % 360;

    return currentRotation + extraTurns + delta;
};
