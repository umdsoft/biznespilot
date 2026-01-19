#!/bin/bash
find resources/js/components -name "*.vue" -type f | while read file; do
  # Get relative path and filename
  filename=$(basename "$file" .vue)
  # Search in all source files for this component name
  grep -r "$filename" --include="*.vue" --include="*.js" --include="*.ts" . 2>/dev/null | \
    grep -v "resources/js/components" | \
    grep -v "node_modules" | \
    grep -v "vendor" | \
    grep -v "/dist/" | \
    grep -v "public/build" > /dev/null
  
  if [ $? -ne 0 ]; then
    echo "$file"
  fi
done
