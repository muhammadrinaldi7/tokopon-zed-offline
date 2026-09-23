const fs = require('fs');
const path = require('path');

const targetDir = 'd:\\APP\\executive-app';
const tabPath = path.join(targetDir, 'src', 'components', 'tabs', 'ProjectSalesTab.tsx');
let content = fs.readFileSync(tabPath, 'utf8');

content = content.replace(
  `  Calendar,
  DollarSign,
  Package,
  Percent,
  SlidersHorizontal,
  ChevronDown,
  ChevronUp,
  ArrowRight,
  Sparkles,`,
  `  DollarSign,
  Package,
  Percent,`
);

fs.writeFileSync(tabPath, content, 'utf8');
console.log('Cleaned up unused imports in ProjectSalesTab.tsx');
