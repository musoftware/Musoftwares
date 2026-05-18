from setuptools import setup, find_packages

setup(
    name="isass-client",
    version="1.0.0",
    description="iSAAS Marketplace API client library for Python desktop tools",
    long_description=open("README.md").read(),
    long_description_content_type="text/markdown",
    author="Musoftware",
    python_requires=">=3.11",
    packages=find_packages(),
    install_requires=[
        "httpx[http2]>=0.27.0",
    ],
    classifiers=[
        "Programming Language :: Python :: 3",
        "License :: OSI Approved :: MIT License",
        "Operating System :: OS Independent",
    ],
)
