import glob
import os
import subprocess
import difflib
import argparse
import multiprocessing
import pickle
import xml.etree.ElementTree as ET
from xml.etree import ElementTree

BLUE = "\033[34m"
BLACK = "\033[0m"
GREEN = "\033[32m"
RED = "\033[31m"
YELLOW = "\033[33m"


def elements_equal(e1, e2):
    if e1.tag != e2.tag:
        return False
    if e1.text != e2.text:
        if  e1.text!=None and e2.text!=None :
            return False
    if e1.tail != e2.tail:
        if e1.tail!=None and e2.tail!=None:
            return False
    if e1.attrib != e2.attrib:
        return False
    if len(e1) != len(e2):
        return False
    return all(elements_equal(c1, c2) for c1, c2 in zip(e1, e2))


def two_xml_equal(xml_str1, xml_str2):
    root1 = ET.fromstring("\n".join([line.strip() for line in xml_str1.split('\n')]))
    root2 = ET.fromstring("\n".join([line.strip() for line in xml_str2.split('\n')]))
    # print(ElementTree.tostring(root1, encoding='utf-8', method='xml').decode(),
    #      "\n\n", ElementTree.tostring(root2, encoding='utf-8', method='xml').decode())

    return elements_equal(root1, root2)


class DirTester:
    class Tester:
        def __init__(self, num_tests_, verbose, show_errors, generated_test, turbo_mode):
            self.num_tests = num_tests_
            self.cur_test = 1
            self.verbose = verbose
            self.show_errors = show_errors
            self.generated_test = generated_test
            self.turbo_mode = turbo_mode

        def run_test(self, src: str):
            files = {}
            file_types = ["src", "out", "rc"]
            for file_type in file_types:
                with open(src + "." + file_type) as file:
                    files[file_type] = file.read()

            command = "php parse.php"

            stderr_output = subprocess.PIPE if not self.show_errors else subprocess.STDOUT
            result = subprocess.run(command,
                                    input=files["src"],
                                    stdout=subprocess.PIPE,
                                    stderr=stderr_output,
                                    encoding="utf-8",
                                    shell=True)

            diff = difflib.unified_diff([line.strip() for line in result.stdout.splitlines()], [line.strip() for line in
                                                                                                files["out"].splitlines()], lineterm='', n=0)

            if files["out"] == "" and result.stdout != "":
                files_same = False

            elif result.stdout == "" and files["out"] != "":
                files_same = False

            elif result.stdout == "" and files["out"] == "":
                files_same = True

            else:
                files_same = two_xml_equal(result.stdout, files["out"])

            # for generated tests
            if self.generated_test:
                if not files_same:
                    if self.turbo_mode:
                        return False

                    print(f"{RED} TEST [{self.cur_test}/{self.num_tests}] {os.path.basename(src)} unsuccessful {BLACK}")
                    print("------------------------------")
                    return False

                if self.turbo_mode:
                    return files["rc"] == str(result.returncode)

                if files["rc"] != str(result.returncode):
                    print(f"{RED} TEST [{self.cur_test}/{self.num_tests}] {os.path.basename(src)} unsuccessful {BLACK}")
                    print(f"{RED} expected return code {files['rc']} got {result.returncode} {BLACK}")
                    print("------------------------------")
                    return False

                print(f"{GREEN} TEST [{self.cur_test}/{self.num_tests}] {os.path.basename(src)} successful {BLACK}")
                print("------------------------------")
                return True

            # for normal tests
            if not files_same:
                print(f"{RED} TEST [{self.cur_test}/{self.num_tests}] {os.path.basename(src)} unsuccessful {BLACK}")
                print("------------------------------")
                if self.verbose:
                    print(files["src"])
                    print()

                if files["rc"] != str(result.returncode):
                    print(f"{RED} expected return code {files['rc']} got {result.returncode} {BLACK}")

                if self.verbose:
                    print(f"{BLUE} True output {BLACK}:")
                    print(files["out"])
                    print(f"{BLUE} User output {BLACK}:")
                    print(result.stdout)
                    print("\n".join(diff))

                print("------------------------------")
                return False

            if files["rc"] != str(result.returncode):
                if self.verbose:
                    print(files["src"])
                    print()

                print(f"{RED} TEST [{self.cur_test}/{self.num_tests}] {os.path.basename(src)} unsuccessful - wrong \
                        return code: expected {files['rc']} got {result.returncode}{BLACK}")
                return False

            print(f"{GREEN} TEST [{self.cur_test}/{self.num_tests}] {os.path.basename(src)} successful {BLACK}")
            print("------------------------------")

            return True


    def __init__(self, directory_: str):
        self.directory = directory_
        self.files = glob.glob(self.directory + "/*.src")
        self.files = list(map(lambda x: x.replace(".src", ""), self.files))

    def test_dir(self, verbose=True, show_errors=True, generated_test=False, turbo_mode=False, queue=None):
        success = 0
        print("{} Running tests for {} {}".format(BLUE, self.directory, BLACK))
        tester = self.Tester(len(self.files), verbose, show_errors, generated_test, turbo_mode)
        for file in self.files:
            success += tester.run_test(file)
            tester.cur_test += 1

        print(f"{YELLOW} Finished {self.directory.split('/')[-1]} - [{success}/{len(self.files)}] successful {BLACK}")
        print()
        if queue is not None:
            serialized = pickle.dumps([success, len(self.files), self.directory])
            queue.put(serialized)
            return

        return success, len(self.files)

def print_help():
    print("""usage: test.py [-h] [--run_generated RUN_GENERATED] [--show_errors SHOW_ERRORS]\n\
            [--verbose VERBOSE] [--test_dir TEST_DIR]\n
        optional arguments:\n\
                ---------------------
            -h, --help\n\tshow this help message and exit\n\n\
                    --run_generated RUN_GENERATED\n\tSet to 1 to run generated code, set to 0 to not run\n\n\
                    --show_errors SHOW_ERRORS\n\tSet to 1 to show errors, set to 0 to not show\n\n\
                    --verbose VERBOSE\n\tSet to 1 to be verbose, set to 0 to not be verbose\n\n\
                    --test_dir TEST_DIR\n\tPath to test dir

  """)
    exit()

parser = argparse.ArgumentParser(add_help=False)
parser.add_argument('--run_generated', type=int, default=0, help='Set to True to run generated code')
parser.add_argument('--turbo_mode', type=int, default=0, help='Use multiprocessing to speed up testing of generated tests')
parser.add_argument('--show_errors', type=int, default=0, help='Set to True to show errors')
parser.add_argument('--verbose', type=int, default=1, help='Set to True to be verbose')
parser.add_argument('--test_dir', type=str, default="./IPP23_parser_tests/", help='Path to the tests')
parser.add_argument('--help', '--h', action="store_true")


try:
    args = parser.parse_args()
except:
    print_help()

if args.help:
    print_help()

if not args.run_generated and args.turbo_mode:
    print("Turbo mode is only available for generated tests")

TEST_DIR = args.test_dir

turbo_mode = args.turbo_mode

generated_success, generated_total = 0, 0
generated_summary = {}

if args.run_generated:
    if args.turbo_mode:
        queue = multiprocessing.Queue()
        procs = []
        generated_folders = glob.glob(os.path.join(TEST_DIR + "GENERATED/*"))
        for generated_folder in generated_folders:
            dir_tester = DirTester(generated_folder)
            proc = multiprocessing.Process(target=dir_tester.test_dir, kwargs={'verbose': False, 'show_errors': args.show_errors,
                                                                               'generated_test':True, 'queue': queue,
                                                                               'turbo_mode': True})
            procs.append(proc)
            proc.start()

        for proc in procs:
            proc.join()

        results = [queue.get() for _ in range(len(generated_folders))]
        deserialized = [pickle.loads(result) for result in results]

        for result1, result2, directory in deserialized:
            generated_success += result1
            generated_total += result2
            directory = directory
            generated_summary[directory.split('/')[-1]] = [result1, result2]
    else:
        for generated_folder in glob.glob(os.path.join(TEST_DIR + "GENERATED/*")):
            dir_tester = DirTester(generated_folder)
            result = dir_tester.test_dir(verbose=False, show_errors=args.show_errors, generated_test=True,)

            generated_success += result[0]
            generated_total += result[1]
            generated_summary[generated_folder.split('/')[-1]] = [result[0], result[1]]


print("Ended them")
success, total = 0, 0
summary = {}
for folder in glob.glob(TEST_DIR + "*")[1:]:
    if "GENERATED" in folder:
        continue

    dir_tester = DirTester(folder)
    result = dir_tester.test_dir(verbose=args.verbose, show_errors=args.show_errors, turbo_mode=turbo_mode)
    summary[folder.split('/')[-1]] = [result[0], result[1]]
    success += result[0]
    total += result[1]

print(f"Total tests: {success}/{total} successfull === {GREEN} {total - success} to go {BLACK}")
print("----------------")
print(f"Total generated tests: {generated_success}/{generated_total}  === {GREEN} {generated_total - generated_success} to go {BLACK}")

# max_folder_len = max([folder for folder in summary], key=len)


def generate_summary(summary):
    for folder, (success, total) in summary.items():
        if (success == total):
            print(f"{folder}: {GREEN} {success}/{total} {BLACK}")
        else:
            print(f"{folder}: {RED} {success}/{total} {BLACK}")

generate_summary(summary)
if args.run_generated:
    print("----------------")
    generate_summary(generated_summary)
