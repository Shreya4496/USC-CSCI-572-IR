from bs4 import BeautifulSoup
from time import sleep as sleeptime
import requests
import json
import csv
from random import randint

USER_AGENT = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'
    }


class SearchEngine:
    @staticmethod
    def search(query, sleep=True):
        if sleep:  # Prevents loading too many pages too soon
            sleeptime(randint(10, 100))
        temp_url = '+'.join(query.split())  # for adding + between words for the query
        url = 'https://duckduckgo.com/html/?q=' + temp_url + "&count=30"
        soup = BeautifulSoup(requests.get(url, headers=USER_AGENT).text, "html.parser")
        new_results = SearchEngine.scrape_search_result(soup)
        return new_results

    @staticmethod
    def scrape_search_result(soup):
        raw_results = soup.find_all("a", attrs={"class": "result__a"})
        results = []
        seen_links = set()
        for result in raw_results:
            link = result.get('href')
            if link not in seen_links:
                results.append(link)
                seen_links.add(link)
            if len(results) == 10:
                break
        return results


class Crawler:
    def __main__(self):
        queries = []
        with open("queries.txt", "r") as file:
            for query in file:
                queries.append(query.rstrip("? \n"))

        main_results = {}
        # for f, query in enumerate(queries):
        #     main_results[query] = SearchEngine.search(query, True)
        #     print(query)
        #     print(len(main_results[query]))
        #
        # with open("Submitted Folder/hw1.json", "w") as file:
        #     json.dump(main_results, file, indent=2)

        query_stats = []
        with open("Submitted Folder/hw1.json", "r") as file:
            main_results = json.load(file)

        with open("google_results.txt", "r") as file:
            ref_results = json.load(file)

        total_overlaps, total_percent, total_rho = 0, 0, 0
        for idx, query in enumerate(queries):
            main_links = main_results[query]
            ref_links = ref_results[query]

            main_links_map = {}
            for pos, val in enumerate(main_links):
                val = self.trimUrl(val)
                main_links_map[val] = pos

            overlaps, sum = 0, 0
            overlap_match = False
            for pos, val in enumerate(ref_links):
                val = self.trimUrl(val)
                if val in main_links_map:
                    overlaps += 1
                    sum += ((pos - main_links_map[val]) ** 2)
                    if pos == main_links_map[val]:
                        overlap_match = True
                    else:
                        overlap_match = False

            if overlaps == 0:
                rho = 0
            elif overlaps == 1:
                rho = 1 if overlap_match else 0
            else:
                rho = 1 - ((6 * sum) / (overlaps * (overlaps ** 2 - 1)))

            query_stats.append(["Query "+str(idx+1), overlaps, overlaps/len(ref_links) * 100.0, rho])
            total_overlaps += overlaps
            total_percent += (overlaps/len(ref_links) * 100.0)
            total_rho += rho

        with open("Submitted Folder/hw1.csv", "w", newline='') as file:
            writer = csv.writer(file)
            writer.writerow(["Queries", "Number of Overlapping Results", "Percent Overlap", "Spearman Coefficient"])
            writer.writerows(query_stats)
            writer.writerow(["Averages", total_overlaps/len(queries), total_percent/len(queries), total_rho/len(queries)])

    def trimUrl(self, link):
        return link.rstrip(" /").replace("www.", "").replace("https://", "").replace("http://", "")


robot = Crawler()
robot.__main__()
