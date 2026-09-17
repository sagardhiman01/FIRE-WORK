/**
 * SparkleAI - Intelligent Fireworks Recommendation & Budget Optimizer Engine
 * Adheres to agency-ai-engineer specifications: data-driven, safety-optimized pyrotechnic curation.
 */

class SparkleAIPlanner {
  constructor() {
    this.occasionProfiles = {
      diwali: {
        name: 'Diwali Grand Festival of Lights',
        skyRatio: 0.40,
        groundRatio: 0.35,
        sparklerRatio: 0.25,
        idealBrands: ['Cock Brand', 'Ravindra Fireworks', 'Sunshine Fireworks'],
        theme: 'Illuminating gold, vibrant green eco-sparkles & continuous festive cheer'
      },
      wedding: {
        name: 'Royal Wedding & Baraat Grand Entrance',
        skyRatio: 0.60,
        groundRatio: 0.20,
        sparklerRatio: 0.20,
        idealBrands: ['Cock Brand', 'Supreme Fireworks', 'Son India'],
        theme: 'High altitude 240 Sky Shots, golden brocade waterfall & royal red carpet fountains'
      },
      newyear: {
        name: 'New Year Countdown & Corporate Gala',
        skyRatio: 0.70,
        groundRatio: 0.15,
        sparklerRatio: 0.15,
        idealBrands: ['Cock Brand', 'Sonny Fireworks', 'Mercury Fireworks'],
        theme: 'Midnight synchronized celestial barrage, countdown multi-color cakes'
      },
      birthday: {
        name: 'Birthday & Private Terrace Celebration',
        skyRatio: 0.30,
        groundRatio: 0.40,
        sparklerRatio: 0.30,
        idealBrands: ['Sunshine Fireworks', 'Supreme Fireworks', 'Vel\'s Fireworks'],
        theme: 'Low smoke, ultra-safe eco sparklers, whistling chakkars & colorful fountains'
      },
      temple: {
        name: 'Temple Pooja & Community Jagran',
        skyRatio: 0.35,
        groundRatio: 0.35,
        sparklerRatio: 0.30,
        idealBrands: ['Son India', 'Ravindra Fireworks', 'Cock Brand'],
        theme: 'Traditional 1000 wala sound ladi, sacred peacock fountains, grand pooja salute'
      }
    };
  }

  generatePlan(params) {
    const { occasion, budget, guests, soundPref } = params;
    const profile = this.occasionProfiles[occasion] || this.occasionProfiles.diwali;

    // Filter available products
    let pool = [...(window.FIREWORKS_PRODUCTS || [])];

    if (soundPref === 'eco') {
      pool = pool.filter(p => p.ecoFriendly);
    } else if (soundPref === 'loud') {
      pool = pool.filter(p => p.noiseLevel.includes('High') || p.category === 'Rockets' || p.category === 'Sound Crackers' || p.category === 'Sky Shots');
    }

    // Allocate budget
    const targetBudget = Math.max(2500, Number(budget) || 10000);
    const selectedItems = [];
    let allocatedTotal = 0;

    // 1. Anchor Sky Shot Cake
    const skyShots = pool.filter(p => p.category === 'Sky Shots').sort((a, b) => b.price - a.price);
    if (skyShots.length > 0) {
      const topCake = skyShots.find(p => p.price <= targetBudget * 0.5) || skyShots[skyShots.length - 1];
      const cakeQty = Math.max(1, Math.floor((targetBudget * 0.45) / topCake.price));
      selectedItems.push({ product: topCake, quantity: cakeQty, role: 'Grand Aerial Finale' });
      allocatedTotal += topCake.price * cakeQty;
    }

    // 2. Fountains / Anaar
    const fountains = pool.filter(p => p.category === 'Flower Pots').sort((a, b) => b.price - a.price);
    if (fountains.length > 0 && allocatedTotal < targetBudget) {
      const fItem = fountains[0];
      const fQty = Math.max(1, Math.floor((targetBudget * 0.20) / fItem.price));
      selectedItems.push({ product: fItem, quantity: fQty, role: 'Mid-Show Illuminating Fountains' });
      allocatedTotal += fItem.price * fQty;
    }

    // 3. Sparklers & Chakkars for family
    const familyCrackers = pool.filter(p => p.category === 'Sparklers' || p.category === 'Chakkars');
    familyCrackers.forEach(item => {
      if (allocatedTotal + item.price <= targetBudget * 0.95) {
        const qty = Math.max(1, Math.floor(guests > 50 ? 3 : 2));
        selectedItems.push({ product: item, quantity: qty, role: 'Opening Celebration & Guest Participation' });
        allocatedTotal += item.price * qty;
      }
    });

    // 4. Fill remaining budget
    if (targetBudget - allocatedTotal > 400) {
      const remainderItem = pool.find(p => p.price <= (targetBudget - allocatedTotal));
      if (remainderItem) {
        selectedItems.push({ product: remainderItem, quantity: 1, role: 'Bonus Celebration Addition' });
        allocatedTotal += remainderItem.price;
      }
    }

    // Calculate show duration estimate
    const estimatedRuntimeMins = Math.min(45, Math.max(10, Math.round(selectedItems.length * 3.5 + (targetBudget / 1500))));
    const minSafetyMeters = soundPref === 'loud' ? 20 : 12;

    return {
      occasionName: profile.name,
      theme: profile.theme,
      allocatedTotal,
      targetBudget,
      items: selectedItems,
      runtimeMins: estimatedRuntimeMins,
      safetyMeters: minSafetyMeters,
      recommendedBrands: profile.idealBrands,
      burnSequence: [
        { phase: '1. Welcoming Ceremony', action: 'Distribute Mega Sparklers & light Mini Color Anaars at entrance perimeter.' },
        { phase: '2. Ground Spectacle', action: 'Ignite Deluxe Chakkars and Peacock Tri-color fountains simultaneously for golden cascade.' },
        { phase: '3. Grand Sky Shot Finale', action: 'Step back to 15m safety perimeter; trigger Grand Sky Shots barrage for synchronized sky illumination.' }
      ]
    };
  }
}

window.sparkleAI = new SparkleAIPlanner();
