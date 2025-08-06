#!/usr/bin/env python3


'''
yara_multiprocessing_scanner.py
- Example implementation of a fast recursive file scanner with multiprocessing using yara-python 
- Speed reaches 50-75% of yara.c
- Works on linux, windows and mac os (uses start method "spawn")
- Runs with python 3 and 2
- Command line parameters aim to be compatible with yara.c (as far as implemented ;)
- By arnim rupp 

Multi licensed:
GNU General Public License v3.0
AGPL 3.0 or later
Creative Commons 4.0 BY

'''

import yara      # pip install yara-python
import argparse
from os import walk, path
from sys import version_info
import multiprocessing
from queue import Empty
from time import sleep

def do_scan(filePath, rules):

    #print("scanning: ", filePath)

    # give yara the filename to scan
    try:
        # can't measure a difference between fast mode or not, probably not worth creating a param as in yara.c
        matches = rules.match(filePath, fast=True)
        if matches:
            for match in matches:
                print(match.rule, filePath)
    except Exception as e:
        pass
        # print("ERROR", "FileScan", "Cannot YARA scan file: %s" % filePath)

def worker(rulesfile, work_queue):

    # here the rules can be compiled because we're after the pickling of multiprocessing
    # print("Compiling rules from %s in %s" % (rulesfile, multiprocessing.current_process().name))
    rules = yara.compile(filepaths={
      'rules':rulesfile
    })

    filePath=""
    shutdown=""
    while not shutdown:
        #print('.', end='', flush=True)
        try:
            filePath = work_queue.get(block=True, timeout=0.1)
            if filePath == 'STOP':
                shutdown = True
            else:
                #print("work work", filePath)
                try:
                    do_scan(filePath, rules)
                except Exception as e:
                    print(e)
            work_queue.task_done()
        except Empty:
            continue
        except Exception as e:
            # print("%s failed on %s with: %s" % (multiprocessing.current_process().name, filePath, e.message))
            pass

    return True

############################### main() ###########################################

def main():

    if path.exists('/tmp/find_threads_use'):

        filesOperation = []
        with open('/tmp/find_threads_use') as f:
            for fileTarget in f.readlines():
                filesOperation.append(fileTarget.split("\n")[0])

        if len(filesOperation) > 0:

            # code works with python2.7 but can't be set to spawn, output differs a bit and it's 15% slower
            if version_info[0] >= 3:
                # spawn is the only method on win
                multiprocessing.set_start_method('spawn')

            # Argument parsing
            parser = argparse.ArgumentParser(description='yara_multiprocessing_scanner.py, the pattern matching swiss army knife in python')
            parser.add_argument('RULES_FILE', help='Path to rules file')
            parser.add_argument('DIR', help='Path to scan')
            parser.add_argument('-r','--recursive',  help='recursively search directories',  action="store_true")
            parser.add_argument('-p','--threads',  help='use the specified NUMBER of threads to scan a directory (default is number of virtual cores)',  type=int, nargs='?')

            args = parser.parse_args()

            #print("rules file: ", args.RULES_FILE)
            rulesfile = args.RULES_FILE

            if args.threads:
                max_proc = args.threads
            else: 
                # spawn as many workers as there are virtual cores (faster than number of physical cores due to the mix of IO and CPU) 
                max_proc = multiprocessing.cpu_count()

            work_queue = multiprocessing.JoinableQueue()
            processes = []

            # print("Spawning %d worker processes" % max_proc )
            for w in range(max_proc):
                p = multiprocessing.Process(target=worker, args=(rulesfile, work_queue))
                p.start()
                processes.append(p)

            # wait for workers to compile rules, TODO: let them send a message when done
            sleep(0.1)

            #for root, directories, files in walk(str(args.DIR), followlinks=False):
            for filename in filesOperation:
                work_queue.put(filename)

            # print("done directory walking")

            # print("waiting for scan processes to finish")
            work_queue.join()
            for x in range(32):
                work_queue.put('STOP')
            # print("cleaning up")
            work_queue.close()
            work_queue.join_thread()

if __name__ == '__main__':
    # Add support for when a program which uses multiprocessing has been frozen to produce a Windows executable. (Has been tested with py2exe, PyInstaller and cx_Freeze.)
    multiprocessing.freeze_support()

    main()