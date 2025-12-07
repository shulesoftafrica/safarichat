<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROI Calculator - SafariChat AI Sales Agent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1F7A8C',
                        secondary: '#FFBB33'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-6xl mx-auto p-6">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">ROI Calculator</h1>
            <p class="text-xl text-gray-600">See exactly how much your AI Sales Agent will generate</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Input Section -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Your Business Details</h2>
                
                <div class="space-y-6">
                    <!-- Business Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Industry Type</label>
                        <select id="businessType" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="ecommerce">E-commerce</option>
                            <option value="financial">Financial Services</option>
                            <option value="education">Education</option>
                            <option value="healthcare">Healthcare</option>
                            <option value="real_estate">Real Estate</option>
                            <option value="professional">Professional Services</option>
                            <option value="retail">Retail</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Team Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sales Team Size</label>
                        <input type="number" id="teamSize" value="5" min="1" max="100"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary">
                        <small class="text-gray-500">Number of people handling sales/customer service</small>
                    </div>

                    <!-- Average Deal Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Average Deal Size ($)</label>
                        <input type="number" id="avgDealSize" value="2500" min="1"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary">
                        <small class="text-gray-500">Average transaction or contract value</small>
                    </div>

                    <!-- Monthly Leads -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Leads</label>
                        <input type="number" id="monthlyLeads" value="200" min="1"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary">
                        <small class="text-gray-500">New prospects contacting you each month</small>
                    </div>

                    <!-- Current Conversion Rate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Conversion Rate (%)</label>
                        <input type="number" id="conversionRate" value="12" min="0" max="100" step="0.1"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary">
                        <small class="text-gray-500">Percentage of leads that become customers</small>
                    </div>

                    <!-- Average Hourly Wage -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Average Team Member Cost ($/hour)</label>
                        <input type="number" id="hourlyWage" value="25" min="5"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary">
                        <small class="text-gray-500">Total cost including salary, benefits, overhead</small>
                    </div>

                    <button onclick="calculateDetailedROI()" 
                            class="w-full bg-primary text-white py-3 rounded-lg font-semibold text-lg hover:bg-primary/90 transition-colors">
                        Calculate My ROI
                    </button>
                </div>
            </div>

            <!-- Results Section -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Your ROI Projection</h2>
                
                <div id="roiResults" class="space-y-6">
                    <div class="text-center text-gray-500 py-12">
                        <div class="text-6xl mb-4">💰</div>
                        <p class="text-lg">Enter your business details to see your personalized ROI projection</p>
                    </div>
                </div>

                <!-- Assumptions -->
                <div id="assumptions" class="hidden mt-8 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">Calculation Assumptions:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• AI improves conversion rates by 25-45%</li>
                        <li>• AI handles 70% of initial customer interactions</li>
                        <li>• Average 3-5 messages per lead interaction</li>
                        <li>• AI responds 24/7 vs business hours only</li>
                        <li>• Based on industry benchmarks and client results</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Industry Benchmarks -->
        <div class="mt-12 bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Industry Benchmarks</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">35%</div>
                    <div class="text-sm text-gray-600">Average Conversion Increase</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600">24/7</div>
                    <div class="text-sm text-gray-600">Availability</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <div class="text-3xl font-bold text-yellow-600">30s</div>
                    <div class="text-sm text-gray-600">Average Response Time</div>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-3xl font-bold text-purple-600">95%</div>
                    <div class="text-sm text-gray-600">Customer Satisfaction</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculateDetailedROI() {
            // Get input values
            const businessType = document.getElementById('businessType').value;
            const teamSize = parseInt(document.getElementById('teamSize').value);
            const avgDealSize = parseInt(document.getElementById('avgDealSize').value);
            const monthlyLeads = parseInt(document.getElementById('monthlyLeads').value);
            const conversionRate = parseFloat(document.getElementById('conversionRate').value) / 100;
            const hourlyWage = parseFloat(document.getElementById('hourlyWage').value);

            // Industry-specific multipliers
            const industryMultipliers = {
                'ecommerce': { conversion: 0.45, efficiency: 0.8 },
                'financial': { conversion: 0.35, efficiency: 0.6 },
                'education': { conversion: 0.30, efficiency: 0.7 },
                'healthcare': { conversion: 0.25, efficiency: 0.5 },
                'real_estate': { conversion: 0.40, efficiency: 0.7 },
                'professional': { conversion: 0.35, efficiency: 0.6 },
                'retail': { conversion: 0.50, efficiency: 0.8 },
                'other': { conversion: 0.35, efficiency: 0.7 }
            };

            const multiplier = industryMultipliers[businessType] || industryMultipliers['other'];

            // Current state calculations
            const currentMonthlyRevenue = monthlyLeads * conversionRate * avgDealSize;
            const currentAnnualRevenue = currentMonthlyRevenue * 12;

            // AI improvements
            const conversionImprovement = multiplier.conversion;
            const newConversionRate = conversionRate * (1 + conversionImprovement);
            const newMonthlyRevenue = monthlyLeads * newConversionRate * avgDealSize;
            const newAnnualRevenue = newMonthlyRevenue * 12;
            const additionalRevenue = newAnnualRevenue - currentAnnualRevenue;

            // Time savings calculations
            const hoursPerLead = 0.75; // Average hours spent per lead
            const aiEfficiency = multiplier.efficiency; // Percentage of work AI can handle
            const hoursSaved = monthlyLeads * hoursPerLead * aiEfficiency;
            const monthlyCostSavings = hoursSaved * hourlyWage;
            const annualCostSavings = monthlyCostSavings * 12;

            // AI service cost calculation
            const estimatedMonthlyMessages = monthlyLeads * 3.5; // Average messages per lead
            const monthlyAICost = calculateAICost(estimatedMonthlyMessages);
            const annualAICost = monthlyAICost * 12;

            // Final ROI calculation
            const totalAnnualBenefit = additionalRevenue + annualCostSavings;
            const netAnnualBenefit = totalAnnualBenefit - annualAICost;
            const roiPercentage = (netAnnualBenefit / annualAICost) * 100;

            // Display results
            displayResults({
                currentAnnualRevenue,
                newAnnualRevenue,
                additionalRevenue,
                annualCostSavings,
                totalAnnualBenefit,
                annualAICost,
                netAnnualBenefit,
                roiPercentage,
                monthlyAICost,
                estimatedMonthlyMessages,
                conversionImprovement: conversionImprovement * 100,
                newConversionRate: newConversionRate * 100,
                hoursSaved
            });
        }

        function calculateAICost(monthlyMessages) {
            if (monthlyMessages <= 497) {
                return 200; // ~$200 equivalent of TSh 49,700
            } else if (monthlyMessages <= 1041) {
                return 375; // ~$375 equivalent of TSh 93,700
            } else if (monthlyMessages <= 1545) {
                return 495; // ~$495 equivalent of TSh 123,600
            } else {
                const overage = monthlyMessages - 1545;
                return 495 + (overage * 0.30); // $0.30 per additional message
            }
        }

        function displayResults(results) {
            const resultsDiv = document.getElementById('roiResults');
            const assumptionsDiv = document.getElementById('assumptions');
            
            resultsDiv.innerHTML = `
                <div class="space-y-6">
                    <!-- Key Metrics -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-700">+${results.roiPercentage.toFixed(0)}%</div>
                            <div class="text-sm text-green-600 font-semibold">ROI Percentage</div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-blue-700">$${results.netAnnualBenefit.toLocaleString()}</div>
                            <div class="text-sm text-blue-600 font-semibold">Net Annual Benefit</div>
                        </div>
                    </div>

                    <!-- Revenue Impact -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">Revenue Impact</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current Annual Revenue:</span>
                                <span class="font-semibold">$${results.currentAnnualRevenue.toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">With AI Annual Revenue:</span>
                                <span class="font-semibold text-green-600">$${results.newAnnualRevenue.toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-gray-800 font-semibold">Additional Revenue:</span>
                                <span class="font-bold text-green-700">+$${results.additionalRevenue.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cost Savings -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">Cost Savings</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Hours Saved/Month:</span>
                                <span class="font-semibold">${results.hoursSaved.toFixed(0)} hours</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Annual Cost Savings:</span>
                                <span class="font-semibold text-blue-600">$${results.annualCostSavings.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>

                    <!-- AI Service Cost -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">AI Service Investment</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Estimated Messages/Month:</span>
                                <span class="font-semibold">${Math.round(results.estimatedMonthlyMessages)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Monthly AI Cost:</span>
                                <span class="font-semibold">$${results.monthlyAICost}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Annual AI Investment:</span>
                                <span class="font-semibold text-orange-600">$${results.annualAICost.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Improvements -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">Performance Improvements</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Conversion Rate Increase:</span>
                                <span class="font-semibold text-purple-600">+${results.conversionImprovement.toFixed(0)}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">New Conversion Rate:</span>
                                <span class="font-semibold">${results.newConversionRate.toFixed(1)}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="bg-gradient-to-r from-primary/10 to-secondary/10 rounded-lg p-6 text-center">
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Summary</h3>
                        <p class="text-gray-700">
                            Your AI Sales Agent will generate <strong class="text-green-700">$${results.netAnnualBenefit.toLocaleString()}</strong> 
                            in additional profit annually, representing a <strong class="text-green-700">${results.roiPercentage.toFixed(0)}% ROI</strong> 
                            on your AI investment.
                        </p>
                    </div>
                </div>
            `;
            
            assumptionsDiv.classList.remove('hidden');
        }

        // Initialize with default calculation
        document.addEventListener('DOMContentLoaded', function() {
            calculateDetailedROI();
        });
    </script>
</body>
</html>