import glob
import os
import subprocess
import difflib
import argparse

BLUE = "\033[34m"
BLACK = "\033[0m"
GREEN = "\033[32m"
RED = "\033[31m"
YELLOW = "\033[33m"


class DirTester:
    class Tester:
        def __init__(self, num_tests_, verbose, show_errors):
            self.num_tests = num_tests_
            self.cur_test = 1
            self.verbose = verbose
            self.show_errors = show_errors

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
            diff_result = list(diff)

            print()
            if diff_result:
                print(f"{RED} TEST [{self.cur_test}/{self.num_tests}] {os.path.basename(src)} unsuccessful {BLACK}")
                if files["rc"] != str(result.returncode):
                    print(f"{RED} expected return code {files['rc']} got {result.returncode} {BLACK}")

                if self.verbose:
                    print(f"{BLUE} True output {BLACK}:")
                    print(files["out"])
                    print(f"{BLUE} User output {BLACK}:")
                    print(result.stdout)
                    print("\n".join(diff_result))

                print("------------------------------")
                return False
            else:
                if files["rc"] != str(result.returncode):
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

    def test_dir(self, verbose=True, show_errors=True):
        success = 0
        print("{} Running tests for {} {}".format(BLUE, os.path.dirname(self.directory), BLACK))
        tester = self.Tester(len(self.files), verbose, show_errors)
        for file in self.files:
            success += tester.run_test(file)
            tester.cur_test += 1

        print(f"{YELLOW} Finished [{success}/{len(self.files)}] successful {BLACK}")
        print()
        return success, len(self.files)


parser = argparse.ArgumentParser(add_help=False)
parser.add_argument('--run_generated', type=int, default=0, help='Set to True to run generated code')
parser.add_argument('--show_errors', type=int, default=0, help='Set to True to show errors')
parser.add_argument('--verbose', type=int, default=1, help='Set to True to be verbose')
parser.add_argument('--help', '--h', action="store_true")


args = parser.parse_args()

if args.help:
    print("""usage: test.py [-h] [--run_generated RUN_GENERATED] [--show_errors SHOW_ERRORS]\n\
               [--verbose VERBOSE]\n\
\n\
optional arguments:\n\
  -h, --help            show this help message and exit\n\
  --run_generated RUN_GENERATED\n\
                        Set to 1 to run generated code, set to 0 to not run\n\
  --show_errors SHOW_ERRORS\n\
                        Set to 1 to show errors, set to 0 to not show\n\
  --verbose VERBOSE     Set to 1 to be verbose, set to 0 to not be verbose\n\
  """
          )
    exit()

TEST_DIR = "tests/"

test_directories = []

generated_success, generated_total = 0, 0

if args.run_generated:
    for generated_folder in glob.glob(os.path.join(TEST_DIR + "GENERATED/*")):
        dir_tester = DirTester(generated_folder)
        result = dir_tester.test_dir(verbose=False, show_errors=args.show_errors)
        generated_success += result[0]
        generated_total += result[1]

success, total = 0, 0
for folder in glob.glob(TEST_DIR + "*")[1:]:
    if "GENERATED" in folder:
        continue

    dir_tester = DirTester(folder)
    result = dir_tester.test_dir(verbose=args.verbose, show_errors=args.show_errors)
    success += result[0]
    total += result[1]

print(f"Total tests: {total}, successful: {success}")
print(f"Total generated tests: {generated_total}, successful: {generated_success}")
