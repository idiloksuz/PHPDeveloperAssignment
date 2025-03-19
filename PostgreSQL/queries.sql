-- queries.sql: Identify duplicates, normalize data, and get statistics
-- Additionally, to finding entries with similar names (case-sensitivity) I choose to remove duplicates with the company website, 
-- because same company may have different names like Acme Inc or Acme Incroprated but it cannot have multiple websites. 
-- I first normalize websites so that they can be compared. 

SELECT 
    regexp_replace(LOWER(name), '[^a-z0-9 ]', '', 'g') AS normalized_name, -- Normalize names by removing special characters
    COUNT(*) AS occurrences, -- Count occurrences of each normalized name
    array_agg(DISTINCT source) AS sources -- Collect all unique sources for each name
FROM companies
GROUP BY regexp_replace(LOWER(name), '[^a-z0-9 ]', '', 'g');

INSERT INTO normalized_companies (name, canonical_website, address)
SELECT INITCAP(name), canonical_website, address -- To make the table more readable, I made the company name capitalized.
FROM (
    SELECT name,
           regexp_replace(LOWER(website), '^www\.|/$', '', 'g') AS canonical_website, -- Normalize website by breaking it down to host url.
           address,
           source,
           ROW_NUMBER() OVER (
               PARTITION BY regexp_replace(LOWER(website), '^www\.|/$', '', 'g') -- All companies that share the same normalized website are grouped together.
               ORDER BY 
                   CASE 
                       WHEN source LIKE 'MANUAL%' THEN 1 -- Highest priority.
                       WHEN source LIKE 'API%' THEN 2 -- Medium priority.
                       WHEN source LIKE 'SCRAPER%' THEN 3 -- Lowest priority.
                       ELSE 4 -- Any unknown sources come last.
                   END
           ) AS row_num
    FROM companies
) subquery
WHERE row_num = 1 -- From the different occurunces of websites I choose the one that has the higher priority source to display.
ORDER BY 
    CASE 
        WHEN source LIKE 'MANUAL%' THEN 1 
        WHEN source LIKE 'API%' THEN 2 
        WHEN source LIKE 'SCRAPER%' THEN 3 
        ELSE 4
    END; -- Ensure final ordering also respects source priority.

-- I counted companies per source, sorted them by their highest count.
-- This query counts how many companies were collected from each source
SELECT source, COUNT(*) AS company_count
FROM companies
GROUP BY source
ORDER BY company_count DESC; -- Sort from highest to lowest number of collected companies
